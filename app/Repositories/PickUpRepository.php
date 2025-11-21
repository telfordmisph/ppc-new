<?php

namespace App\Repositories;

use App\Traits\TrendAggregation;

use Illuminate\Support\Facades\DB;
use App\Constants\WipConstants;
use App\Helpers\WipTrendParser;

class PickUpRepository
{
  use TrendAggregation;

  private const TABLE_NAME = 'ppc_pickupdb';
  private const PART_NAME_TABLE = 'ppc_partnamedb';

  public function getTotalQuantity($startDate, $endDate)
  {
    return DB::table(self::TABLE_NAME)
      ->whereBetween('DATE_CREATED', [$startDate, $endDate])
      ->sum('QTY');
  }

  public function getFactoryTotalQuantityRanged($factory, $startDate, $endDate)
  {
    return DB::table(self::TABLE_NAME . ' as pickup')
      ->joinSub(
        DB::table(self::PART_NAME_TABLE)
          ->selectRaw('Partname, Factory')
          ->distinct(),
        'part',
        function ($join) {
          $join->on('pickup.PARTNAME', '=', 'part.Partname');
        }
      )
      ->whereBetween('pickup.DATE_CREATED', [$startDate, $endDate])
      ->where('part.Factory', $factory)
      ->sum('pickup.QTY');
  }

  public function getFactoryPlTotalQuantity($factory, $pl, $startDate, $endDate)
  {
    $partSubquery = DB::table(self::PART_NAME_TABLE)
      ->selectRaw('Partname, Factory, PL')
      ->distinct();

    return DB::table(self::TABLE_NAME . ' as pickup')
      ->joinSub($partSubquery, 'part', function ($join) {
        $join->on('pickup.PARTNAME', '=', 'part.Partname');
      })
      ->whereBetween('pickup.DATE_CREATED', [$startDate, $endDate])
      ->where('part.Factory', $factory)
      ->where('part.PL', $pl)
      ->sum('pickup.QTY');
  }

  public function filterByPackageName($query, ?array $packageNames)
  {
    if (is_string($packageNames)) {
      $packageNames = explode(',', $packageNames);
    }
    $packageNames = array_filter((array) $packageNames, fn($p) => !empty($p));

    if (empty($packageNames)) return $query;

    return $query->whereIn('pickup.PACKAGE', $packageNames);
  }


  public function getPackageSummary($chartStatus, $startDate, $endDate)
  {
    $query = DB::table(self::TABLE_NAME . ' as pickup')
      ->selectRaw('pickup.PACKAGE, SUM(pickup.QTY) as total_quantity, COUNT(DISTINCT pickup.LOTID) as total_lots')
      ->whereBetween('pickup.DATE_CREATED', [$startDate, $endDate]);

    switch ($chartStatus) {
      case 'F1':
      case 'F2':
      case 'F3':
        $query->join(self::PART_NAME_TABLE . ' as part', 'pickup.PARTNAME', '=', 'part.Partname')
          ->where('part.Factory', $chartStatus);
        break;

      case 'PL1':
      case 'PL6':
        $query->join(self::PART_NAME_TABLE . ' as part', 'pickup.PARTNAME', '=', 'part.Partname')
          ->where('part.PL', $chartStatus);
        break;

      case 'all':
        break;

      default:
        $query->whereRaw('DATE(pickup.DATE_CREATED) = CURDATE()');
        break;
    }

    return $query->groupBy('pickup.PACKAGE')
      ->orderByDesc('total_quantity')
      ->get();
  }
  public function getPickUpTrend($packageName, $period, $lookBack, $offsetDays)
  {
    $trends = [];

    foreach (WipConstants::FACTORIES as $factory) {
      $key = strtolower($factory) . '_trend';

      $query = DB::table(self::TABLE_NAME . ' as pickup')
        ->selectRaw('pickup.PACKAGE, SUM(pickup.QTY) as total_quantity, COUNT(DISTINCT pickup.LOTID) as total_lots');
      $query->join(self::PART_NAME_TABLE . ' as part', 'pickup.PARTNAME', '=', 'part.Partname')
        ->where('part.Factory', $factory);
      $query->groupBy('pickup.PACKAGE')
        ->orderByDesc('total_quantity');
      $query = $this->filterByPackageName($query, $packageName);
      $query = $this->applyTrendAggregation(
        $query,
        $period,
        $lookBack,
        $offsetDays,
        'pickup.DATE_CREATED',
        [
          'SUM(pickup.QTY)' => 'total_quantity',
          'COUNT(DISTINCT pickup.LOTID)' => 'total_lots'
        ]
      )->get();

      $trends[$key] = $query;
    }

    $mergedTrends = WipTrendParser::parseTrendsByPeriod($trends);

    return response()->json(array_merge($trends, [
      'data' => $mergedTrends,
      'status' => 'success',
      'message' => 'Data retrieved successfully',
    ]));
  }
}
