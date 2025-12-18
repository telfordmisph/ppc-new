<?php

namespace App\Traits;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use App\Constants\WipConstants;
use Illuminate\Support\Facades\Log;
use App\Helpers\SqlDebugHelper;

trait TrendAggregationTrait
{
  public function applyTrendAggregation(
    Builder $query,
    string $period = 'daily',
    $startDate,
    $endDate,
    string $column = 'Date_Loaded',
    array $aggregateColumns = [
      'SUM(Qty)' => 'total_wip',
      'COUNT(DISTINCT Lot_Id)' => 'total_lots'
    ],
    array $additionalFields = [],
    string $workweeks = '',
  ): Builder {
    $query = clone $query;
    $query->select([]);

    $groupByOrderBy = WipConstants::PERIOD_GROUP_BY[$period];

    $aggSelects = collect($aggregateColumns)->map(function ($alias, $expr) {
      if (is_int($expr)) {
        $expr = $alias;
        $alias = Str::slug($expr, '_');
      }
      return "$expr as $alias";
    })->implode(', ');

    $extraFields = !empty($additionalFields) ? implode(', ', $additionalFields) . ', ' : '';
    $groupByFields = collect($additionalFields)->map(fn($f) => preg_split('/\s+as\s+/i', $f)[0])->toArray();

    switch (strtolower($period)) {
      case 'daily':
        $query->selectRaw("
                $extraFields
                Date($column) as day,
                $aggSelects
            ");

        break;

      case 'weekly':
        $weekRanges = $this->analogCalendarRepo->getDatesByWorkWeekRange($workweeks)['range'];

        if (empty($weekRanges)) {
          throw new \Exception('No date associated with workweeks. Got: ' . json_encode($workweeks));
        }

        $query->where(function ($q) use ($column, $weekRanges) {
          foreach ($weekRanges as $range) {
            // $q->orWhereBetween($column, [$range->startDate, $range->endDate]);
            $q->orWhere(function ($query) use ($column, $range) {
              $query->where($column, '>=', $range->startDate)
                ->where($column, '<', $range->endDate);
            });
          }
        });

        $case = implode(' ', array_map(function ($range) use ($column) {
          return "WHEN $column BETWEEN '{$range->startDate}' AND '{$range->endDate}' THEN CONCAT('w', '{$range->workweek}')";
        }, $weekRanges));

        $weekCase = implode(' ', array_map(function ($range) use ($column) {
          return "WHEN $column BETWEEN '{$range->startDate}' AND '{$range->endDate}' THEN '{$range->workweek}'";
        }, $weekRanges));

        $query->selectRaw("
              $extraFields
              CASE 
                  $case
                  ELSE NULL
              END as workweek,
              YEAR($column) as year,
              CASE 
                  $weekCase
                  ELSE NULL
              END as week,
              $aggSelects
          ");

        break;

      case 'monthly':
        $query->selectRaw("
                $extraFields
                YEAR($column) as year,
                MONTH($column) as month,
                $aggSelects
            ");

        break;

      case 'quarterly':
        $query->selectRaw("
                $extraFields
                YEAR($column) as year,
                QUARTER($column) as quarter,
                $aggSelects
            ");

        break;

      case 'yearly':
        $query->selectRaw("
                $extraFields
                YEAR($column) as year,
                $aggSelects
            ");

        break;
    }

    $query->groupBy(array_merge($groupByFields, $groupByOrderBy));
    foreach ($groupByOrderBy as $col) {
      $query->orderBy($col);
    }

    if ($period !== 'weekly' || empty($workweeks)) {
      // $query->whereBetween($column, [$startDate, $endDate]);
      $query->where($column, '>=', $startDate)
        ->where($column, '<', $endDate);
    }
    Log::info("sql :D : " . SqlDebugHelper::prettify($query->toSql(), $query->getBindings()));

    return $query;
  }

  /**
   * Merge multiple trend arrays by a key, summing numeric fields.
   *
   * @param string $keyField The key to merge on, e.g., 'dateKey'
   * @param array ...$arrays Arrays to merge
   * @return array Merged and sorted array
   */
  public function mergeTrendsByKey(string $keyField = 'dateKey', $ignore, array ...$arrays)
  {
    $merged = [];

    foreach (array_merge(...$arrays) as $item) {
      $key = $item[$keyField];

      if (!isset($merged[$key])) {
        $merged[$key] = $item;
      } else {
        foreach ($item as $field => $value) {
          if ($field === $keyField || in_array($field, $ignore, true)) {
            continue;
          }

          if (isset($merged[$key][$field]) && is_numeric($merged[$key][$field]) && is_numeric($value)) {
            $merged[$key][$field] += $value;
          } else {
            $merged[$key][$field] = $value;
          }
        }
      }
    }

    // Sort by keyField
    usort($merged, fn($a, $b) => strcmp($a[$keyField], $b[$keyField]));

    return $merged;
  }
}
