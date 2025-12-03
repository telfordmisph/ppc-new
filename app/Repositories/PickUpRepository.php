<?php

namespace App\Repositories;

use App\Traits\TrendAggregationTrait;

use Illuminate\Support\Facades\DB;
use App\Constants\WipConstants;
use App\Helpers\WipTrendParser;
use Illuminate\Support\Facades\Log;
use App\Helpers\SqlDebugHelper;
use App\Helpers\MergeAndAggregate;

class PickUpRepository
{
  use TrendAggregationTrait;
  protected $analogCalendarRepo;

  public function __construct(
    AnalogCalendarRepository $analogCalendarRepo,
  ) {
    $this->analogCalendarRepo = $analogCalendarRepo;
  }

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

  public function filterByPackageName($query, ?array $packageNames, $factory)
  {
    if (is_string($packageNames)) {
      $packageNames = explode(',', $packageNames);
    }
    $packageNames = array_filter((array) $packageNames, fn($p) => !empty($p));

    if (empty($packageNames)) return $query;

    return $query->whereIn('pickup.PACKAGE', $packageNames);

    // TODO should i do this too
    // if (!empty($packageNames)) {
    //   $query->where(function ($q) use ($packageNames) {
    //     $q->where($this->wherePackageAlias('f3_pkg.package_name', $packageNames))
    //       ->orWhereIn('raw.dimension', $packageNames);
    //   });
    // }
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
  public function getPickUpTrend($packageName, $period, $startDate, $endDate, $workweeks)
  {
    $trends = [];

    $aggregateColumn = [
      'SUM(pickup.QTY)' => 'total_quantity',
      'COUNT(DISTINCT pickup.LOTID)' => 'total_lots'
    ];

    foreach (WipConstants::FACTORIES as $factory) {
      $key = strtolower($factory) . '_trend';
      $query = DB::table(self::TABLE_NAME . ' as pickup');
      // ->select(['pickup.PACKAGE']);
      $query->join(self::PART_NAME_TABLE . ' as part', 'pickup.PARTNAME', '=', 'part.Partname')
        ->where('part.Factory', strtoupper($factory));
      // $query->groupBy('pickup.PACKAGE')
      // ->orderByDesc('total_quantity');

      $query = $this->filterByPackageName($query, $packageName, $factory);
      $query = $this->applyTrendAggregation(
        $query,
        $period,
        $startDate,
        $endDate,
        'pickup.DATE_CREATED',
        // * If in the future this get complicated, apply WipConstants::FACTORY_AGGREGATES
        $aggregateColumn,
        ['pickup.PACKAGE as package'],
        workweeks: $workweeks,
      );


      $trends[$key] = $query;
    }

    $groupByOrderBy = WipConstants::PERIOD_GROUP_BY[$period];

    $f1Sub = DB::query()
      ->fromSub(clone($trends['f1_trend']), 'f1')
      ->select([...$groupByOrderBy, ...$aggregateColumn])
      ->selectRaw("'F1' as factory, package");

    $f2Sub = DB::query()
      ->fromSub(clone($trends['f2_trend']), 'f2')
      ->select([...$groupByOrderBy, ...$aggregateColumn])
      ->selectRaw("'F2' as factory, package");

    $f3Sub = DB::query()
      ->fromSub(clone($trends['f3_trend']), 'f3')
      ->select([...$groupByOrderBy, ...$aggregateColumn])
      ->selectRaw("'F3' as factory, package");

    Log::info(SqlDebugHelper::prettify($f1Sub->toSql(), $f1Sub->getBindings()));
    Log::info(SqlDebugHelper::prettify($f2Sub->toSql(), $f2Sub->getBindings()));
    Log::info(SqlDebugHelper::prettify($f3Sub->toSql(), $f3Sub->getBindings()));

    $combined = $f1Sub
      ->unionAll($f3Sub)
      ->unionAll($f2Sub);

    // TODO: bruh its overwriting

    // $combined = $f3Sub;

    $finalResults = DB::query()->fromSub($combined, 'wip_union')
      ->select([...$groupByOrderBy, ...$aggregateColumn, 'factory']);
    foreach ([...$groupByOrderBy, 'factory'] as $col) {
      $finalResults->orderBy($col);
    }

    Log::info(SqlDebugHelper::prettify($finalResults->toSql(), $finalResults->getBindings()));

    $trends['f1_trend'] = MergeAndAggregate::mergeAndAggregate([$trends['f1_trend']->get()], $groupByOrderBy);
    $trends['f2_trend'] = MergeAndAggregate::mergeAndAggregate([$trends['f2_trend']->get()], $groupByOrderBy);
    $trends['f3_trend'] = MergeAndAggregate::mergeAndAggregate([$trends['f3_trend']->get()], $groupByOrderBy);

    Log::info(json_encode($trends));

    $trends['overall_trend'] = MergeAndAggregate::mergeAndAggregate([$finalResults->get()], $groupByOrderBy, ['factory']);
    $mergedTrends = WipTrendParser::parseTrendsByPeriod($trends);
    Log::info(json_encode($mergedTrends));

    $merged = $this->mergeTrendsByKey('dateKey', $mergedTrends);

    return response()->json(array_merge([
      'data' => $merged,
      'status' => 'success',
      'message' => 'Data retrieved successfully',
    ]));
  }
}
