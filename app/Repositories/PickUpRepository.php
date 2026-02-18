<?php

namespace App\Repositories;

use App\Traits\TrendAggregationTrait;

use Illuminate\Support\Facades\DB;
use App\Constants\WipConstants;
use App\Helpers\WipTrendParser;
use Illuminate\Support\Facades\Log;
use App\Helpers\SqlDebugHelper;
use App\Helpers\MergeAndAggregate;
use Carbon\Carbon;
use App\Models\PickUp;
use App\Models\F3Pickup;
use App\Services\BulkUpserter;

class PickUpRepository
{
  use TrendAggregationTrait;
  protected $analogCalendarRepo;
  private const aggregateColumn = [
    'SUM(pickup.QTY)' => 'total_wip',
    'COUNT(DISTINCT pickup.LOTID)' => 'total_lots'
  ];

  public function __construct(
    AnalogCalendarRepository $analogCalendarRepo,
  ) {
    $this->analogCalendarRepo = $analogCalendarRepo;
  }

  private const TABLE_NAME = 'ppc_pickupdb';
  private const PART_NAME_TABLE = 'ppc_partnamedb';

  public function getTotalQuantity($startDate, $endDate)
  {
    return DB::table(self::TABLE_NAME . ' as pickup')
      ->where('DATE_CREATED', ">=", $startDate)
      ->where('DATE_CREATED', "<", $endDate)

      ->sum('QTY');
  }

  public function getFactoryTotalQuantityRanged($factory, $startDate, $endDate)
  {
    $factory = strtoupper($factory);

    $query = DB::table(self::TABLE_NAME . ' as pickup')
      ->where('DATE_CREATED', '>=', $startDate)
      ->where('DATE_CREATED', '<', $endDate);

    if ($factory === 'F3') {
      // join F3 table
      $query->join('f3_pickup', 'f3_pickup.ppc_pickup_id', '=', 'pickup.id_pickup');
    } elseif (in_array($factory, ['F1', 'F2'])) {
      $partNames = DB::table(self::PART_NAME_TABLE)
        ->where('Factory', $factory)
        ->pluck('Partname');

      $query->whereIn('pickup.PARTNAME', $partNames);
    }

    return $query->sum('pickup.QTY');
  }

  public function getFactoryPlTotalQuantity($factory, $pl, $startDate, $endDate)
  {
    $factory = strtoupper($factory);

    $query = DB::table(self::TABLE_NAME . ' as pickup')
      ->where('DATE_CREATED', '>=', $startDate)
      ->where('DATE_CREATED', '<', $endDate);

    if ($factory === 'F3') {
      $query->join('f3_pickup', 'f3_pickup.ppc_pickup_id', '=', 'pickup.id_pickup');
    } else {
      // Get Partnames that match both Factory and PL
      $partNames = DB::table(self::PART_NAME_TABLE)
        ->where('Factory', $factory)
        ->where('PL', $pl)
        ->pluck('Partname');

      $query->whereIn('pickup.PARTNAME', $partNames);
    }

    if ($factory !== 'F3') {
      $query->whereIn('pickup.PARTNAME', $partNames);
    }

    return $query->sum('pickup.QTY');
  }

  public function filterByPackageName($query, ?array $packageNames)
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
      ->selectRaw('PACKAGE, SUM(QTY) as total_wip, COUNT(DISTINCT LOTID) as total_lots')
      ->where('DATE_CREATED', '>=', $startDate)
      ->where('DATE_CREATED', '<', $endDate);

    $chartStatus = strtoupper($chartStatus);

    if (in_array($chartStatus, ['F1', 'F2'])) {
      $partNames = DB::table(self::PART_NAME_TABLE)
        ->where('Factory', $chartStatus)
        ->pluck('Partname');
      $query->whereIn('pickup.PARTNAME', $partNames);
    } elseif ($chartStatus === 'F3') {
      $query->join('f3_pickup', 'f3_pickup.ppc_pickup_id', '=', 'pickup.id_pickup');
    } elseif (in_array($chartStatus, ['PL1', 'PL6'])) {
      $partNames = DB::table(self::PART_NAME_TABLE)
        ->where('PL', $chartStatus)
        ->pluck('Partname');
      $query->whereIn('pickup.PARTNAME', $partNames);
    }

    return $query->groupBy('pickup.PACKAGE')
      ->orderByDesc('total_wip')
      ->get();
  }

  public function getBaseTrend($factory, $packageName, $period, $startDate, $endDate, $workweeks, $aggregate = true)
  {
    $query = DB::table(self::TABLE_NAME . ' as pickup');
    $query = $this->filterByPackageName($query, $packageName);

    $factory = strtoupper($factory);
    if ($factory === 'F3') {
      $query->join('f3_pickup', 'f3_pickup.ppc_pickup_id', '=', 'pickup.id_pickup');
    } elseif (in_array($factory, ['F1', 'F2'])) {
      $partNames = DB::table(self::PART_NAME_TABLE)
        ->where('Factory', $factory)
        ->pluck('Partname');
      $query->whereIn('pickup.PARTNAME', $partNames);
    }

    if ($aggregate) {
      $query = $this->applyTrendAggregation(
        $query,
        $period,
        $startDate,
        $endDate,
        'pickup.DATE_CREATED',
        self::aggregateColumn,
        ['pickup.PACKAGE as package'],
        workRange: $this->analogCalendarRepo->getDatesByWorkWeekRange($workweeks)['range']
      );
    } else {
      $query->where('pickup.DATE_CREATED', '>=', $startDate)
        ->where('pickup.DATE_CREATED', '<', $endDate)
        ->select('pickup.PARTNAME', 'pickup.LOTID', 'pickup.QTY', 'pickup.PACKAGE', 'pickup.LC', 'pickup.ADDED_BY', 'pickup.DATE_CREATED');
    }

    return $query;
  }

  public function getPickUpTrend($packageName, $period, $startDate, $endDate, $workweeks)
  {
    $trends = [];

    foreach (WipConstants::FACTORIES as $factory) {
      $key = strtolower($factory) . '_trend';

      $query = $this->getBaseTrend($factory, $packageName, $period, $startDate, $endDate, $workweeks);
      $trends[$key] = $query;
    }

    $groupByOrderBy = WipConstants::PERIOD_GROUP_BY[$period];

    $f1Sub = DB::query()
      ->fromSub(clone($trends['f1_trend']), 'f1')
      ->select([...$groupByOrderBy, ...self::aggregateColumn])
      ->selectRaw("'F1' as factory, package");

    $f2Sub = DB::query()
      ->fromSub(clone($trends['f2_trend']), 'f2')
      ->select([...$groupByOrderBy, ...self::aggregateColumn])
      ->selectRaw("'F2' as factory, package");

    $f3Sub = DB::query()
      ->fromSub(clone($trends['f3_trend']), 'f3')
      ->select([...$groupByOrderBy, ...self::aggregateColumn])
      ->selectRaw("'F3' as factory, package");

    $combined = $f1Sub
      ->unionAll($f3Sub)
      ->unionAll($f2Sub);

    // TODO: bruh its overwriting

    // $combined = $f3Sub;

    $finalResults = DB::query()->fromSub($combined, 'wip_union')
      ->select([...$groupByOrderBy, ...self::aggregateColumn, 'factory']);
    foreach ([...$groupByOrderBy, 'factory'] as $col) {
      $finalResults->orderBy($col);
    }


    $trends['f1_trend'] = MergeAndAggregate::mergeAndAggregate([$trends['f1_trend']->get()], $groupByOrderBy);
    $trends['f2_trend'] = MergeAndAggregate::mergeAndAggregate([$trends['f2_trend']->get()], $groupByOrderBy);
    $trends['f3_trend'] = MergeAndAggregate::mergeAndAggregate([$trends['f3_trend']->get()], $groupByOrderBy);

    $trends['overall_trend'] = MergeAndAggregate::mergeAndAggregate([$finalResults->get()], $groupByOrderBy, ['factory']);
    $mergedTrends = WipTrendParser::parseTrendsByPeriod($trends);

    $merged = $this->mergeTrendsByKey('dateKey', ['label'], $mergedTrends);

    return response()->json(array_merge([
      'data' => $merged,
      'status' => 'success',
      'message' => 'Data retrieved successfully',
    ]));
  }

  private function upsertPickup($data = null, $importedBy = null)
  {
    $upserter = new BulkUpserter(
      new PickUp(),
      [
        'LC' => 'required|integer',
        'QTY' => 'required|integer',
        'PARTNAME' => 'required',
        'PACKAGE' => 'required',
        'LOTID' => 'required',
      ],
      attributeNames: [
        'LC' => 'LC',
        'QTY' => 'QTY',
        'PARTNAME' => 'PARTNAME',
        'PACKAGE' => 'PACKAGE',
        'LOTID' => 'LOTID',
      ]
    );

    $result = $upserter->update($data, $importedBy);

    if (!empty($result['errors'])) {
      throw new \Exception(
        'Some rows failed validation: ' . implode('; ', $result['errorMessages'])
      );
    }

    return $result;
  }

  public function insertMany(array $data, ?int $importedBy = null)
  {
    $result = $this->upsertPickup($data, $importedBy);
    return ['status' => 'success', 'inserted' => $result['inserted'], 'updated' => $result['updated']];
  }

  public function insertF3Many(array $data, ?int $importedBy = null)
  {
    $result = $this->upsertPickup($data, $importedBy);

    $rows = collect($result['inserted'])
      ->map(fn($id) => [
        'ppc_pickup_id' => $id,
      ])
      ->toArray();

    F3Pickup::insert($rows);

    return ['status' => 'success', 'inserted' => $result['inserted'], 'updated' => $result['updated']];
  }
}
