<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

trait TrendAggregation
{
  public function applyTrendAggregation(
    Builder $query,
    string $period = 'daily',
    int $lookBack = 3,
    int $offsetDays = 0,
    string $column = 'Date_Loaded',
    array $aggregateColumns = [
      'SUM(Qty)' => 'total_quantity',
      'COUNT(DISTINCT Lot_Id)' => 'total_lots'
    ],
    array $additionalFields = [],
    string $workweeks = '',
  ): Builder {
    $query = clone $query;

    $now = Carbon::now()->subDays($offsetDays)->endOfDay();

    // Build aggregate select statements
    $aggSelects = collect($aggregateColumns)->map(function ($alias, $expr) {
      if (is_int($expr)) {
        $expr = $alias;
        $alias = Str::slug($expr, '_');
      }
      return "$expr as $alias";
    })->implode(', ');

    $extraFields = !empty($additionalFields) ? implode(', ', $additionalFields) . ', ' : '';

    switch (strtolower($period)) {
      case 'daily':
        $startDate = (clone $now)->subDays($lookBack)->startOfDay();
        $query->selectRaw("
                $extraFields
                DATE($column) as day,
                $aggSelects
            ")
          ->groupBy(array_merge($additionalFields, ['day']))
          ->orderBy('day');
        break;

      case 'weekly':
        $weekRanges = $this->analogCalendarRepo->getDatesByWorkWeekRange($workweeks);

        if (empty($weekRanges)) {
          throw new \Exception('No date associated with workweeks. Got: ' . json_encode($workweeks));
        }

        $query->where(function ($q) use ($column, $weekRanges) {
          foreach ($weekRanges as $range) {
            $q->orWhereBetween($column, [$range->startDate, $range->endDate]);
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
          ")
          // CONCAT('year ', YEAR($column)) as year,
          // CONCAT('week', WEEK($column, 1)) as week,
          ->groupBy(array_merge($additionalFields, ['year', 'week', 'workweek']))
          ->orderBy('year')
          ->orderBy('week');
        break;

      case 'monthly':
        $startDate = (clone $now)->subMonths($lookBack)->startOfMonth();
        $query->selectRaw("
                $extraFields
                YEAR($column) as year,
                MONTH($column) as month,
                $aggSelects
            ")
          ->groupBy(array_merge($additionalFields, ['year', 'month']))
          ->orderBy('year')
          ->orderBy('month');
        break;

      case 'quarterly':
        $startDate = (clone $now)->subQuarters($lookBack)->startOfQuarter();
        $query->selectRaw("
                $extraFields
                YEAR($column) as year,
                QUARTER($column) as quarter,
                $aggSelects
            ")
          ->groupBy(array_merge($additionalFields, ['year', 'quarter']))
          ->orderBy('year')
          ->orderBy('quarter');
        break;

      case 'yearly':
        $startDate = (clone $now)->subYears($lookBack)->startOfYear();
        $query->selectRaw("
                $extraFields
                YEAR($column) as year,
                $aggSelects
            ")
          ->groupBy(array_merge($additionalFields, ['year']))
          ->orderBy('year');
        break;
    }

    if ($period !== 'weekly' || empty($workweeks)) {
      $query->whereBetween($column, [$startDate, $now]);
    }

    return $query;
  }




  // public function applyTrendAggregation(
  //   Builder $query,
  //   string $period = 'weekly',
  //   int $lookBack = 3,
  //   int $offsetDays = 0,
  //   string $column = 'Date_Loaded',
  //   string $quantityColumn = 'Qty',
  //   string $lotColumn = 'Lot_Id'
  // ): Builder {
  //   $now = Carbon::now()->subDays($offsetDays)->endOfDay();

  //   switch (strtolower($period)) {
  //     case 'daily':
  //       $startDate = (clone $now)->subDays($lookBack)->startOfDay();
  //       $query->selectRaw("DATE($column) as day, SUM($quantityColumn) as total_quantity, COUNT(DISTINCT $lotColumn) as total_lots")
  //         ->groupBy('day')
  //         ->orderBy('day');
  //       break;

  //     case 'weekly':
  //       $startDate = (clone $now)->subWeeks($lookBack)->startOfWeek();
  //       $query->selectRaw("YEAR($column) as year, WEEK($column, 1) as week, SUM($quantityColumn) as total_quantity, COUNT(DISTINCT $lotColumn) as total_lots")
  //         ->groupBy('year', 'week')
  //         ->orderBy('year')
  //         ->orderBy('week');
  //       break;

  //     case 'monthly':
  //       $startDate = (clone $now)->subMonths($lookBack)->startOfMonth();
  //       $query->selectRaw("YEAR($column) as year, MONTH($column) as month, SUM($quantityColumn) as total_quantity, COUNT(DISTINCT $lotColumn) as total_lots")
  //         ->groupBy('year', 'month')
  //         ->orderBy('year')
  //         ->orderBy('month');
  //       break;

  //     case 'quarterly':
  //       $startDate = (clone $now)->subMonths($lookBack * 3)->startOfQuarter();
  //       $query->selectRaw("YEAR($column) as year, QUARTER($column) as quarter, SUM($quantityColumn) as total_quantity, COUNT(DISTINCT $lotColumn) as total_lots")
  //         ->groupBy('year', 'quarter')
  //         ->orderBy('year')
  //         ->orderBy('quarter');
  //       break;

  //     case 'yearly':
  //       $startDate = (clone $now)->subYears($lookBack)->startOfYear();
  //       $query->selectRaw("YEAR($column) as year, SUM($quantityColumn) as total_quantity, COUNT(DISTINCT $lotColumn) as total_lots")
  //         ->groupBy('year')
  //         ->orderBy('year');
  //       break;

  //     default:
  //       throw new \InvalidArgumentException("Invalid period: {$period}");
  //   }

  //   return $query->whereBetween($column, [$startDate, $now]);
  // }
}
