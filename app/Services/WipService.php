<?php

namespace App\Services;

use App\Repositories\AnalogCalendarRepository;
use App\Repositories\F1F2WipRepository;
use App\Repositories\F1F2OutRepository;
use App\Repositories\F3WipRepository;
use App\Repositories\F3OutRepository;
use App\Repositories\PickUpRepository;
use App\Services\PackageCapacityService;
use App\Helpers\SqlDebugHelper;
use App\Helpers\WipTrendParser;
use App\Helpers\MergeAndAggregate;
use App\Constants\WipConstants;
use App\Traits\NormalizeStringTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Traits\TrendAggregationTrait;
use App\Repositories\PackageGroupRepository;
use App\Repositories\F3PackageNamesRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Exception;

class WipService
{
  use TrendAggregationTrait;
  use NormalizeStringTrait;
  protected $analogCalendarRepo;
  protected $capacityRepo;
  protected $f1f2WipRepo;
  protected $f1f2OutRepo;
  protected $f3WipRepo;
  protected $f3OutRepo;
  protected $pickUpRepo;

  protected $packageGroupRepo;

  protected $f3packageNamesRepo;
  private const TODAY_WIP_CACHE_KEY = "today_wip";
  private const F1F2_TABLE = "customer_data_wip";
  public function __construct(
    AnalogCalendarRepository $analogCalendarRepo,
    F1F2WipRepository $f1f2WipRepo,
    F1F2OutRepository $f1f2WipOutRepo,
    F3WipRepository $f3WipRepo,
    F3OutRepository $f3OutRepo,
    PickUpRepository $pickUpRepo,
    PackageCapacityService $packageCapacityService,
    PackageGroupRepository $packageGroupRepo,
    F3PackageNamesRepository $f3PackageNamesRepo
  ) {
    $this->analogCalendarRepo = $analogCalendarRepo;
    $this->f1f2WipRepo = $f1f2WipRepo;
    $this->f1f2OutRepo = $f1f2WipOutRepo;
    $this->f3WipRepo = $f3WipRepo;
    $this->f3OutRepo = $f3OutRepo;
    $this->pickUpRepo = $pickUpRepo;
    $this->capacityRepo = $packageCapacityService;
    $this->packageGroupRepo = $packageGroupRepo;
    $this->f3packageNamesRepo = $f3PackageNamesRepo;
  }

  private function applyDateOrWorkweekFilter($query, $dateColumn, $useWorkweek, $workweek, $startDate = null, $endDate = null)
  {
    if ($useWorkweek) {
      $workweekArray = array_map('intval', explode(' ', $workweek));

      $weekRanges = $this->analogCalendarRepo->getWorkweekRanges($workweekArray);

      return $query->where(function ($q) use ($dateColumn, $weekRanges) {
        foreach ($weekRanges as $range) {
          $q->orWhere(function ($q2) use ($dateColumn, $range) {
            $q2->where($dateColumn, '>=', $range->start_date)
              ->where($dateColumn, '<', $range->end_date);
          });
        }
      });
    }

    return $query->where(function ($q) use ($dateColumn, $startDate, $endDate) {
      $q->where($dateColumn, '>=', $startDate)
        ->where($dateColumn, '<', $endDate);
    });
  }


  public function getTodayWip()
  {
    return Cache::remember(self::TODAY_WIP_CACHE_KEY, now()->addHours(23), function () {
      $endDate = Carbon::now()->endOfDay();
      $startDate = Carbon::now()->subDays(25)->startOfDay();

      $f3DataRaw = $this->f3WipRepo->baseF3Query(false)
        ->selectRaw('DATE(date_loaded) AS report_date, SUM(f3.qty) AS f3')
        // ->whereBetween('date_loaded', [$startDate, $endDate])
        ->where('date_loaded', ">=", $startDate)
        ->where('date_loaded', "<", $endDate)
        ->groupBy(DB::raw('date_loaded'))
        ->get();

      $f3Data = [];
      foreach ($f3DataRaw as $row) {
        $f3Data[$row->report_date] = (int) $row->f3;
      }

      $f1Data = DB::table(self::F1F2_TABLE . ' as wip')
        ->selectRaw('DATE(wip.Date_Loaded) AS report_date, SUM(wip.Qty) AS f1_wip')
        ->whereNotIn('wip.Focus_Group', ['DLT', 'WLT', 'SOF'])
        // ->whereBetween('wip.Date_Loaded', [$startDate, $endDate]);
        ->where('wip.Date_Loaded', ">=", $startDate)
        ->where('wip.Date_Loaded', "<", $endDate);

      $f1Data = $this->f1f2WipRepo->f1Filters(
        $f1Data,
        WipConstants::TODAY_WIP_INCLUDED_STATIONS,
        WipConstants::TODAY_WIP_EXCLUDED_STATIONS,
      )->groupBy(DB::raw('DATE(wip.Date_Loaded)'));

      $f2Data = DB::table(self::F1F2_TABLE . ' as wip')
        ->selectRaw('DATE(wip.Date_Loaded) AS report_date, SUM(wip.Qty) AS f2_wip');

      $f2Data = $this->f1f2WipRepo->applyF2Filters($f2Data, [...WipConstants::EWAN_PROCESS, 'Q-PITRANS1', 'GTTRANS_BE'], 'wip')
        // ->whereBetween('wip.Date_Loaded', [$startDate, $endDate])
        ->where('wip.Date_Loaded', ">=", $startDate)
        ->where('wip.Date_Loaded', "<", $endDate)
        ->groupBy(DB::raw('DATE(wip.Date_Loaded)'));

      $f1f2Data = DB::query()
        ->fromSub($f1Data, 'f1')
        ->joinSub($f2Data, 'f2', function ($join) {
          $join->on('f1.report_date', '=', 'f2.report_date');
        })
        ->selectRaw('f1.report_date, f1.f1_wip, f2.f2_wip')
        ->get();

      $data = [];
      foreach ($f1f2Data as $row) {
        $reportDate = $row->report_date;
        $f3Value = $f3Data[$reportDate] ?? 0;

        $data[] = [
          'date'  => $reportDate,
          'total' => (int) $row->f1_wip + (int) $row->f2_wip + $f3Value,
          'f1'    => (int) $row->f1_wip,
          'f2'    => (int) $row->f2_wip,
          'f3'    => $f3Value,
        ];
      }


      return response()->json($data);
    });
  }

  public function getOverallWip($startDate, $endDate, $useWorkweek, $workweek)
  {
    // return response()->json([
    //   'f1_total_wip' => 0,
    //   'f2_total_wip' => 0,
    //   'f3_total_wip' => 0,
    //   'total_f1_pl1' => 0,
    //   'total_f1_pl6' => 0,
    //   'total_f2_pl1' => 0,
    //   'total_f2_pl6' => 0,
    //   'total_f3_pl1' => 0,
    //   'total_f3_pl6' => 0,
    //   'total_wip' => 0,
    //   'status' => 'success',
    //   'message' => 'Data retrieved successfully'
    // ]);

    $f1Query = DB::table(self::F1F2_TABLE . ' as wip')
      ->selectRaw('SUM(wip.Qty) AS f1_total_wip');
    $f1Query = $this->f1f2WipRepo->f1Filters($f1Query, ['GTREEL'], WipConstants::REEL_EXCLUDED_STATIONS_F1_OVERALL, 'wip');

    $f1Query = $this->applyDateOrWorkweekFilter($f1Query, 'wip.Date_Loaded', $useWorkweek, $workweek, $startDate, $endDate);
    $f1QueryResult = $f1Query->first();

    $f2Query = DB::table(self::F1F2_TABLE . ' as wip')
      ->selectRaw('SUM(wip.Qty) AS f2_total_wip');
    $f2Query = $this->f1f2WipRepo->applyF2Filters($f2Query, WipConstants::EXCLUDED_STATIONS_F2, 'wip');

    $f2Query = $this->applyDateOrWorkweekFilter($f2Query, 'wip.Date_Loaded', $useWorkweek, $workweek, $startDate, $endDate);
    $f2QueryResult = $f2Query->first();

    $f1pl6Query = DB::table(self::F1F2_TABLE . ' as wip')
      ->selectRaw('SUM(wip.Qty) AS f1pl6_total_wip');
    $f1pl6Query = $this->f1f2WipRepo->f1Filters($f1pl6Query, ['GTREEL'], WipConstants::REEL_EXCLUDED_STATIONS_F1_PL, 'wip');
    $f1pl6Query = $this->f1f2WipRepo->joinPL($f1pl6Query, WipConstants::SPECIAL_PART_NAMES, 'PL6');

    $f1pl6Query = $this->applyDateOrWorkweekFilter($f1pl6Query, 'wip.Date_Loaded', $useWorkweek, $workweek, $startDate, $endDate);

    // Log::info("f1pl6Query: ");
    // Log::info(SqlDebugHelper::prettify($f1pl6Query->toSql(), $f1pl6Query->getBindings()));
    $f1pl6QueryResult = $f1pl6Query->first();

    $f1pl1Query = DB::table(self::F1F2_TABLE . ' as wip')
      ->selectRaw('SUM(wip.Qty) AS f1pl1_total_wip');
    $f1pl1Query = $this->f1f2WipRepo->f1Filters($f1pl1Query, ['GTREEL'], WipConstants::REEL_EXCLUDED_STATIONS_F1_PL, 'wip');
    $f1pl1Query = $this->f1f2WipRepo->joinPL($f1pl1Query, WipConstants::SPECIAL_PART_NAMES, 'PL1');

    $f1pl1Query = $this->applyDateOrWorkweekFilter($f1pl1Query, 'wip.Date_Loaded', $useWorkweek, $workweek, $startDate, $endDate);
    $f1pl1QueryResult = $f1pl1Query->first();

    $f2pl6Query = DB::table(self::F1F2_TABLE . ' as wip')
      ->selectRaw('SUM(wip.Qty) AS f2pl6_total_wip');
    $f2pl6Query = $this->f1f2WipRepo->applyF2Filters($f2pl6Query, WipConstants::EWAN_PROCESS, 'wip');
    $f2pl6Query = $this->f1f2WipRepo->joinPL($f2pl6Query, WipConstants::SPECIAL_PART_NAMES, 'PL6');

    $f2pl6Query = $this->applyDateOrWorkweekFilter($f2pl6Query, 'wip.Date_Loaded', $useWorkweek, $workweek, $startDate, $endDate);
    $f2pl6QueryResult = $f2pl6Query->first();

    $f2pl1Query = DB::table(self::F1F2_TABLE . ' as wip')
      ->selectRaw('SUM(wip.Qty) AS f2pl1_total_wip');
    $f2pl1Query = $this->f1f2WipRepo->applyF2Filters($f2pl1Query, WipConstants::EXCLUDED_STATIONS_F2, 'wip');
    $f2pl1Query = $this->f1f2WipRepo->joinPL($f2pl1Query, WipConstants::SPECIAL_PART_NAMES, 'PL1');

    $f2pl1Query = $this->applyDateOrWorkweekFilter($f2pl1Query, 'wip.Date_Loaded', $useWorkweek, $workweek, $startDate, $endDate);
    $f2pl1QueryResult = $f2pl1Query->first();

    $f3Total = $this->f3WipRepo->baseF3Query()
      ->selectRaw("SUM(f3.Qty) AS f3_total_wip");

    $f3Total = $this->applyDateOrWorkweekFilter($f3Total, 'f3.date_loaded', $useWorkweek, $workweek, $startDate, $endDate)
      ->first();

    $f3PlTotals = $this->f3WipRepo->baseF3Query(true)
      ->selectRaw("
          SUM(CASE WHEN plref.production_line = 'PL1' THEN f3.Qty ELSE 0 END) AS f3pl1_total_wip,
          SUM(CASE WHEN plref.production_line = 'PL6' THEN f3.Qty ELSE 0 END) AS f3pl6_total_wip
      ");

    $f3PlTotals = $this->applyDateOrWorkweekFilter($f3PlTotals, 'f3.date_loaded', $useWorkweek, $workweek, $startDate, $endDate)
      ->first();

    $f3TotalQty = (int) $f3Total->f3_total_wip;

    $f1Total = (int) ($f1QueryResult->f1_total_wip ?? 0);
    $f2Total = (int) ($f2QueryResult->f2_total_wip ?? 0);

    $grandTotal = $f1Total + $f2Total + $f3TotalQty;

    return response()->json([
      'f1_total_wip' => $f1Total,
      'f2_total_wip' => $f2Total,
      'f3_total_wip' => $f3TotalQty,
      'total_f1_pl1' => (int) $f1pl1QueryResult->f1pl1_total_wip,
      'total_f1_pl6' => (int) $f1pl6QueryResult->f1pl6_total_wip,
      'total_f2_pl1' => (int) $f2pl1QueryResult->f2pl1_total_wip,
      'total_f2_pl6' => (int) $f2pl6QueryResult->f2pl6_total_wip,
      'total_f3_pl1' => (int) $f3PlTotals->f3pl1_total_wip,
      'total_f3_pl6' => (int) $f3PlTotals->f3pl6_total_wip,
      'total_wip' => $grandTotal,
      'status' => 'success',
      'message' => 'Data retrieved successfully'
    ]);
  }

  // TODO TO BE DELETED ????????????????????????????
  // private function getOverallTrend($packageName, $period, $startDate, $endDate, $workweek, $trendType, $aggregateColumns)
  // {
  //   $context = $trendType === 'wip' ? 'wip' : 'out';
  //   $type = $aggregateColumns === 'wip' || $aggregateColumns === 'wip-lot'
  //     ? $aggregateColumns
  //     : 'wip-lot';

  //   $f1f2Query = $this->getFactoryTrend(
  //     "F1F2",
  //     $packageName,
  //     $period,
  //     $startDate,
  //     $endDate,
  //     workweeks: $workweek,
  //     dateColumn: WipConstants::FACTORY_AGGREGATES['F1F2'][$context]['dateColumn'],
  //     aggregateColumns: WipConstants::FACTORY_AGGREGATES['F1F2'][$context][$type]
  //   );

  //   $f3Query =  $this->getFactoryTrend(
  //     "F3",
  //     $packageName,
  //     $period,
  //     $startDate,
  //     $endDate,
  //     workweeks: $workweek,
  //     dateColumn: WipConstants::FACTORY_AGGREGATES['F3'][$context]['dateColumn'],
  //     aggregateColumns: WipConstants::FACTORY_AGGREGATES['F3'][$context][$type]
  //   );

  //   $groupByOrderBy = WipConstants::PERIOD_GROUP_BY[$period];

  //   $f1f2Sub = DB::query()->fromSub($f1f2Query, 'f1f2')
  //     ->select([...$groupByOrderBy, ...array_values(WipConstants::FACTORY_AGGREGATES['F1F2'][$context][$type])]);

  //   $f3Sub = DB::query()->fromSub($f3Query, 'f3')
  //     ->select([...$groupByOrderBy, ...array_values(WipConstants::FACTORY_AGGREGATES['F3'][$context][$type])]);

  //   $combined = $f1f2Sub->unionAll($f3Sub);

  //   // Log::info(SqlDebugHelper::prettify($combined->toSql(), $combined->getBindings()));

  //   $finalResults = DB::query()->fromSub($combined, 'wip_union')
  //     ->select([...$groupByOrderBy, ...array_values(WipConstants::FACTORY_AGGREGATES['All'][$context][$type])]);
  //   foreach ($groupByOrderBy as $col) {
  //     $finalResults->orderBy($col);
  //   }

  //   return $finalResults->get();
  // }

  public function getAllWipTrendByPackage($workweeks, $packageName, $period, $startDate, $endDate)
  {
    $trends = [];

    // foreach (WipConstants::FACTORIES as $factory) {
    //   $key = strtolower($factory) . '_trend';
    //   $trends[$key] = $this->getFactoryTrend(
    //     $factory,
    //     $packageName,
    //     $period,
    //     $startDate,
    //     $endDate,
    //     workweeks: $workweek,
    //     dateColumn: WipConstants::FACTORY_AGGREGATES[$factory]['wip']['dateColumn'],
    //     aggregateColumns: WipConstants::FACTORY_AGGREGATES[$factory]['wip']['wip-lot']
    //   )->get();
    // }

    $trends["f1_trend"] = $this->f1f2WipRepo->getTrend("F1", $packageName, $period, $startDate, $endDate, workweeks: $workweeks)->get();
    $trends["f2_trend"] = $this->f1f2WipRepo->getTrend("F2", $packageName, $period, $startDate, $endDate, workweeks: $workweeks)->get();
    $trends['f3_trend'] = $this->f3WipRepo->getTrend($packageName, $period, $startDate, $endDate, $workweeks);

    Log::info("getAllWipTrendByPackage trends: " . json_encode($trends));
    $periodGroupBy = WipConstants::PERIOD_GROUP_BY[$period];
    $trends['overall_trend'] = MergeAndAggregate::mergeAndAggregate([$trends['f1_trend'], $trends['f2_trend'], $trends['f3_trend']], $periodGroupBy);

    $mergedTrends = WipTrendParser::parseTrendsByPeriod($trends);
    // Log::info("Overall WIP By Package Trend: " . json_encode($mergedTrends));
    return response()->json(array_merge($trends, [
      'data' => $mergedTrends,
      'status' => 'success',
      'message' => 'Data retrieved successfully',
    ]));
  }

  public function getOverallResidual($startDate, $endDate)
  {
    // TODO: no f3 residual?

    $result = new \stdClass();

    $fBaseQuery = DB::table(self::F1F2_TABLE . ' as wip')
      // ->whereBetween('wip.Date_Loaded', [$startDate, $endDate])
      ->where('wip.Date_Loaded', ">=", $startDate)
      ->where('wip.Date_Loaded', "<", $endDate)
      ->where('wip.Station', 'GTTRES_T');

    $result->f1_total_wip = (clone $fBaseQuery)
      ->where(fn($q) => $this->f1f2WipRepo->applyF1Filters($q, WipConstants::F1_EXCLUDED_PLANT, 'wip'))
      ->sum('QTY');

    $result->f2_total_wip = (clone $fBaseQuery)
      ->where(fn($q) => $this->f1f2WipRepo->applyF2Filters($q, [], 'wip'))
      ->sum('QTY');

    $result->total_wip = $result->f1_total_wip + $result->f2_total_wip;

    $result->f1pl1_total_wip = $this->f1f2WipRepo->joinPL((clone $fBaseQuery), WipConstants::SPECIAL_PART_NAMES, 'PL1')
      ->where(fn($q) => $this->f1f2WipRepo->applyF1Filters($q, WipConstants::F1_EXCLUDED_PLANT, 'wip'))
      ->sum('Qty');

    $result->f2pl1_total_wip = $this->f1f2WipRepo->joinPL((clone $fBaseQuery), WipConstants::SPECIAL_PART_NAMES, 'PL1')
      ->where(fn($q) => $this->f1f2WipRepo->applyF2Filters($q, [], 'wip'))
      ->sum('Qty');

    $result->f1pl6_total_wip = $this->f1f2WipRepo->joinPL((clone $fBaseQuery), WipConstants::SPECIAL_PART_NAMES, 'PL6')
      ->where(fn($q) => $this->f1f2WipRepo->applyF1Filters($q, WipConstants::F1_EXCLUDED_PLANT, null))
      ->sum('Qty');

    $result->f2pl6_total_wip = $this->f1f2WipRepo->joinPL((clone $fBaseQuery), WipConstants::SPECIAL_PART_NAMES, 'PL6')
      ->where(fn($q) => $this->f1f2WipRepo->applyF2Filters($q, [], 'wip'))
      ->sum('Qty');


    return response()->json([
      'total_wip' => (int) $result->total_wip,
      'f1_total_wip' => (int) $result->f1_total_wip,
      'f2_total_wip' => (int) $result->f2_total_wip,
      'total_f1_pl1' => (int) $result->f1pl1_total_wip,
      'total_f1_pl6' => (int) $result->f1pl6_total_wip,
      'total_f2_pl1' => (int) $result->f2pl1_total_wip,
      'total_f2_pl6' => (int) $result->f2pl6_total_wip,
      'total_pl1' => (int) $result->f1pl1_total_wip + (int) $result->f2pl1_total_wip,
      'total_pl6' => (int) $result->f1pl6_total_wip + (int) $result->f2pl6_total_wip,
      'status' => 'success',
      'message' => 'Data retrieved successfully'
    ]);
  }

  public function getPackageResidualSummary($chartStatus, $startDate, $endDate)
  {
    switch ($chartStatus) {
      case 'all':
        $query = DB::table(self::F1F2_TABLE . ' as wip')
          ->selectRaw('wip.Package_Name AS PACKAGE, SUM(Qty) AS total_wip, COUNT(DISTINCT Lot_Id) AS total_lots')
          ->where('wip.Station', 'GTTRES_T')
          // ->whereBetween('wip.Date_Loaded', [$startDate, $endDate]);
          ->where('wip.Date_Loaded', ">=", $startDate)
          ->where('wip.Date_Loaded', "<", $endDate);

        if ($chartStatus === 'all') {
          $query->where(function ($q) {
            $q->where(function ($sub) {
              $sub->where(fn($q) => $this->f1f2WipRepo->applyF1Filters($q, WipConstants::F1_EXCLUDED_PLANT, 'wip'));
            })
              ->orWhere(function ($sub) {
                $sub->where(fn($q) => $this->f1f2WipRepo->applyF2Filters($q, [], 'wip'));
              });
          });
        }

        $query->groupBy('PACKAGE')
          ->orderByDesc('total_wip');
        break;

      case 'F1':
        $query = DB::table(self::F1F2_TABLE . ' as wip')
          ->selectRaw('wip.Package_Name AS PACKAGE, SUM(Qty) AS total_wip, COUNT(DISTINCT Lot_Id) AS total_lots')
          ->where('wip.Station', 'GTTRES_T')
          // ->whereBetween('wip.Date_Loaded', [$startDate, $endDate]);
          ->where('wip.Date_Loaded', ">=", $startDate)
          ->where('wip.Date_Loaded', "<", $endDate);
        $query->where(fn($q) => $this->f1f2WipRepo->applyF1Filters($q, WipConstants::F1_EXCLUDED_PLANT, 'wip'));

        $query->groupBy('PACKAGE')
          ->orderByDesc('total_wip');
        break;

      case 'F2':
      case 'F3':
      case 'PL1':
      case 'PL6':
        $query = DB::table('ppc_pickupdb')
          ->selectRaw('ppc_pickupdb.PACKAGE AS PACKAGE, SUM(QTY) AS total_wip, COUNT(DISTINCT LOTID) AS total_lots')
          ->join('ppc_partnamedb as partname', 'ppc_pickupdb.PARTNAME', '=', 'partname.Partname')
          // ->whereBetween('ppc_pickupdb.DATE_CREATED', [$startDate, $endDate]);
          ->where('ppc_pickupdb.DATE_CREATED', ">=", $startDate)
          ->where('ppc_pickupdb.DATE_CREATED', "<", $endDate);

        if ($chartStatus === 'F2' || $chartStatus === 'F3') {
          $query->where('partname.Factory', strtoupper($chartStatus));
        } elseif ($chartStatus === 'PL1' || $chartStatus === 'PL6') {
          $query->where('partname.PL', $chartStatus);
        }

        $query->groupBy('PACKAGE')
          ->orderByDesc('total_wip');
        break;

      default:
        $query = DB::table('ppc_pickupdb')
          ->selectRaw('PACKAGE AS PACKAGE, SUM(QTY) AS total_wip, COUNT(DISTINCT LOTID) AS total_lots')
          ->whereDate('DATE_CREATED', now())
          ->groupBy('PACKAGE')
          ->orderByDesc('total_wip');
        break;
    }

    $results = $query->get();

    return response()->json([
      'data' => $results,
      'status' => 'success',
      'message' => 'Data retrieved successfully'
    ]);
  }
  public function getWIPQuantityAndLotsTotal($useWorkweek, $workweek, $startDate = null, $endDate = null, $includePL = true)
  {
    // NOTE: might be suitable to have another filtration here (for production line & factory)
    // current solution: client-side grouping for production line
    // -----------------------------
    //  BASE QUERY SETUP
    // -----------------------------
    $baseQuery = DB::table(self::F1F2_TABLE . ' as wip')
      ->selectRaw(
        $includePL
          ? 'wip.Package_Name, plref.production_line as PL, SUM(wip.Qty) AS total_wip, COUNT(DISTINCT wip.Lot_Id) as total_lots'
          : 'wip.Package_Name, SUM(wip.Qty) AS total_wip, COUNT(DISTINCT wip.Lot_Id) as total_lots'
      );

    if ($includePL) {
      $baseQuery = $this->f1f2WipRepo->joinPL($baseQuery);
    }

    $baseQuery = $this->applyDateOrWorkweekFilter($baseQuery, 'wip.Date_Loaded', $useWorkweek, $workweek, $startDate, $endDate);
    $baseQuery = $baseQuery->groupBy($includePL ? ['Package_Name', 'plref.production_line'] : ['Package_Name']);

    // -----------------------------
    //  F1 QUERIES
    // -----------------------------
    $f1Query = (clone $baseQuery)
      ->where(fn($q) => $this->f1f2WipRepo->f1Filters($q, WipConstants::REEL_TRANSFER_B3, WipConstants::REEL_TRANSFER_EXCLUDED_STATIONS_F1, 'wip'));

    Log::info("F1 query: " . SqlDebugHelper::prettify($f1Query->toSql(), $f1Query->getBindings()));

    // -----------------------------
    //  F2 QUERIES
    // -----------------------------
    $f2Query = (clone $baseQuery)
      ->where(fn($q) => $this->f1f2WipRepo->applyF2Filters($q, WipConstants::EWAN_PROCESS, 'wip'));

    // -----------------------------
    //  F3 QUERIES
    // -----------------------------
    $f3Query = $this->f3WipRepo->baseF3Query($includePL);
    $f3Query = $this->applyDateOrWorkweekFilter($f3Query, 'f3.date_loaded', $useWorkweek, $workweek, $startDate, $endDate);
    $f3Query = $f3Query->selectRaw(
      $includePL
        ? 'f3_pkg.package_name as Package_Name, plref.production_line as PL, SUM(f3.qty) AS total_wip, COUNT(DISTINCT f3.lot_number) AS total_lots'
        : 'f3_pkg.package_name as Package_Name, SUM(f3.qty) AS total_wip, COUNT(DISTINCT f3.lot_number) AS total_lots'
    );
    if ($includePL) {
      $f3Query->groupBy('f3_pkg.package_name', 'plref.production_line');
    } else {
      $f3Query->groupBy('f3_pkg.package_name');
    }

    // =====================================================================
    //  SECTION 1: RESULTS (PACKAGE TOTALS)
    // =====================================================================

    $joinCondition = $includePL
      ? fn($join, $prefix, $suffix) => $join->on("$prefix.Package_Name", '=', "$suffix.Package_Name")->on("$prefix.PL", '=', "$suffix.PL")
      : fn($join, $prefix, $suffix) => $join->on("$prefix.Package_Name", '=', "$suffix.Package_Name");

    // f1 LEFT JOIN others
    $f1Left = DB::table(DB::raw("({$f1Query->toSql()}) as f1"))
      ->mergeBindings($f1Query)
      ->leftJoin(DB::raw("({$f2Query->toSql()}) as f2"), fn($join) => $joinCondition($join, 'f1', 'f2'))
      ->mergeBindings($f2Query)
      ->leftJoin(DB::raw("({$f3Query->toSql()}) as f3"), fn($join) => $joinCondition($join, 'f1', 'f3'))
      ->mergeBindings($f3Query)
      ->select(
        'f1.Package_Name as Package_Name',
        $includePL ? 'f1.PL as PL' : DB::raw("'N/A' as PL"),
        DB::raw('COALESCE(f1.total_wip, 0) as f1_total_wip'),
        DB::raw('COALESCE(f2.total_wip, 0) as f2_total_wip'),
        DB::raw('COALESCE(f3.total_wip, 0) as f3_total_wip'),
        DB::raw('COALESCE(f1.total_lots, 0) as f1_total_lots'),
        DB::raw('COALESCE(f2.total_lots, 0) as f2_total_lots'),
        DB::raw('COALESCE(f3.total_lots, 0) as f3_total_lots')
      );

    Log::info("f1Left: " . SqlDebugHelper::prettify($f1Left->toSql(), $f1Left->getBindings()));

    // f2 LEFT JOIN others
    $f2Left = DB::table(DB::raw("({$f2Query->toSql()}) as f2"))
      ->mergeBindings($f2Query)
      ->leftJoin(DB::raw("({$f1Query->toSql()}) as f1"), fn($join) => $joinCondition($join, 'f2', 'f1'))
      ->mergeBindings($f1Query)
      ->leftJoin(DB::raw("({$f3Query->toSql()}) as f3"), fn($join) => $joinCondition($join, 'f2', 'f3'))
      ->mergeBindings($f3Query)
      ->select(
        'f2.Package_Name as Package_Name',
        $includePL ? 'f2.PL as PL' : DB::raw("'N/A' as PL"),
        DB::raw('COALESCE(f1.total_wip, 0) as f1_total_wip'),
        DB::raw('COALESCE(f2.total_wip, 0) as f2_total_wip'),
        DB::raw('COALESCE(f3.total_wip, 0) as f3_total_wip'),
        DB::raw('COALESCE(f1.total_lots, 0) as f1_total_lots'),
        DB::raw('COALESCE(f2.total_lots, 0) as f2_total_lots'),
        DB::raw('COALESCE(f3.total_lots, 0) as f3_total_lots')
      );

    // f3 LEFT JOIN others
    $f3Left = DB::table(DB::raw("({$f3Query->toSql()}) as f3"))
      ->mergeBindings($f3Query)
      ->leftJoin(DB::raw("({$f1Query->toSql()}) as f1"), fn($join) => $joinCondition($join, 'f3', 'f1'))
      ->mergeBindings($f1Query)
      ->leftJoin(DB::raw("({$f2Query->toSql()}) as f2"), fn($join) => $joinCondition($join, 'f3', 'f2'))
      ->mergeBindings($f2Query)
      ->select(
        'f3.Package_Name as Package_Name',
        $includePL ? 'f3.PL as PL' : DB::raw("'N/A' as PL"),
        DB::raw('COALESCE(f1.total_wip, 0) as f1_total_wip'),
        DB::raw('COALESCE(f2.total_wip, 0) as f2_total_wip'),
        DB::raw('COALESCE(f3.total_wip, 0) as f3_total_wip'),
        DB::raw('COALESCE(f1.total_lots, 0) as f1_total_lots'),
        DB::raw('COALESCE(f2.total_lots, 0) as f2_total_lots'),
        DB::raw('COALESCE(f3.total_lots, 0) as f3_total_lots')
      );

    $lfcspQuery = DB::table(self::F1F2_TABLE . ' as wip')
      ->selectRaw(
        $includePL
          ? "'LFCSP' AS Package_Name, 'PL1' AS PL, SUM(wip.Qty) AS total_wip, COUNT(DISTINCT wip.Lot_Id) AS total_lots"
          : "'LFCSP' AS Package_Name, SUM(wip.Qty) AS total_wip, COUNT(DISTINCT wip.Lot_Id) AS total_lots"
      )
      ->whereIn('wip.Part_Name', WipConstants::SPECIAL_PART_NAMES);
    $lfcspQuery = $this->f1f2WipRepo->f1Filters($lfcspQuery, WipConstants::REEL_TRANSFER_B3, WipConstants::REEL_TRANSFER_EXCLUDED_STATIONS_F1, 'wip');

    $lfcspQuery = $lfcspQuery->groupBy($includePL ? [DB::raw("'LFCSP'"), DB::raw("'PL1'")] : [DB::raw("'LFCSP'")]);
    // ->orderByDesc('total_wip');
    $lfcspQuery = $this->applyDateOrWorkweekFilter($lfcspQuery, 'wip.Date_Loaded', $useWorkweek, $workweek, $startDate, $endDate);

    $lfcspLeft = DB::table(DB::raw("({$lfcspQuery->toSql()}) as f1"))
      ->mergeBindings($lfcspQuery)
      ->leftJoin(DB::raw("({$f2Query->toSql()}) as f2"), fn($join) => $joinCondition($join, 'f1', 'f2'))
      ->mergeBindings($f2Query)
      ->leftJoin(DB::raw("({$f3Query->toSql()}) as f3"), fn($join) => $joinCondition($join, 'f1', 'f3'))
      ->mergeBindings($f3Query)
      ->select(
        'f1.Package_Name as Package_Name',
        $includePL ? 'f1.PL as PL' : DB::raw("'N/A' as PL"),
        DB::raw('COALESCE(f1.total_wip, 0) as f1_total_wip'),
        DB::raw('COALESCE(f2.total_wip, 0) as f2_total_wip'),
        DB::raw('COALESCE(f3.total_wip, 0) as f3_total_wip'),
        DB::raw('COALESCE(f1.total_lots, 0) as f1_total_lots'),
        DB::raw('COALESCE(f2.total_lots, 0) as f2_total_lots'),
        DB::raw('COALESCE(f3.total_lots, 0) as f3_total_lots')
      );

    $results = DB::table(DB::raw("(
      {$f1Left->toSql()}
      UNION ALL
      {$f2Left->toSql()}
      UNION ALL
      {$f3Left->toSql()}
      UNION ALL
      {$lfcspLeft->toSql()}
      ) as fulljoin"))
      ->mergeBindings($f1Left)
      ->mergeBindings($f2Left)
      ->mergeBindings($f3Left)
      ->mergeBindings($lfcspLeft)
      ->select(
        'Package_Name',
        $includePL ? 'PL' : DB::raw("'N/A' as PL"),
        DB::raw('MAX(f1_total_wip) as f1_total_wip'),
        DB::raw('MAX(f2_total_wip) as f2_total_wip'),
        DB::raw('MAX(f3_total_wip) as f3_total_wip'),
        DB::raw('(MAX(f1_total_wip) + MAX(f2_total_wip) + MAX(f3_total_wip)) as total_wip'),
        DB::raw('MAX(f1_total_lots) as f1_total_lots'),
        DB::raw('MAX(f2_total_lots) as f2_total_lots'),
        DB::raw('MAX(f3_total_lots) as f3_total_lots'),
        DB::raw('(MAX(f1_total_lots) + MAX(f2_total_lots) + MAX(f3_total_lots)) as total_lots')
      )
      ->groupBy($includePL ? ['Package_Name', 'PL'] : ['Package_Name'])
      ->orderByDesc('total_wip');

    Log::info("results: " . SqlDebugHelper::prettify($results->toSql(), $results->getBindings()));

    $results = $results->get();

    $totalwip = $results->sum('total_wip');

    // =====================================================================
    //  RESPONSE
    // =====================================================================
    return response()->json([
      'status' => 'success',
      'message' => 'Highly optimized data retrieved successfully',
      'total_wip' => $totalwip,
      'data' => $results,
    ]);
  }

  public function getBaseFactoryQuery()
  {
    $baseQuery = DB::table(self::F1F2_TABLE . ' as wip');
    // ->selectRaw(
    //   'wip.Package_Name, plref.production_line as PL, wip.Date_Loaded, Qty'
    // );

    $baseQuery = $this->f1f2WipRepo->joinPL($baseQuery);


    return $baseQuery;
  }

  // TODO to be deleted ????????????????????????????
  public function getWIPQuantityAndLotsTotalNew($packageName, $period, $startDate, $endDate, $workweek)
  {
    $baseQuery = $this->getBaseFactoryQuery();
    $baseF3Query = $this->f3WipRepo->baseF3Query(true);

    $f1QueryWip = (clone $baseQuery)
      ->where(fn($q) => $this->f1f2WipRepo->f1Filters($q, WipConstants::REEL_TRANSFER_B3, WipConstants::REEL_TRANSFER_EXCLUDED_STATIONS_F1, 'wip'));
    $f1QueryWip = $this->f1f2WipRepo->filterByPackageName($f1QueryWip, $packageName, 'f1');

    $f1QueryOuts = $this->f1f2OutRepo->getF1QueryByPackage($packageName);

    $f2QueryWip = (clone $baseQuery)
      ->where(fn($q) => $this->f1f2WipRepo->applyF2Filters($q, WipConstants::EWAN_PROCESS, 'wip'));
    $f2QueryWip = $this->f1f2WipRepo->filterByPackageName($f2QueryWip, $packageName, 'f2');

    $f2QueryOuts = $this->f1f2OutRepo->getF2QueryByPackage($packageName);

    $f3QueryWip = $baseF3Query;
    $f3QueryWip = $this->f3WipRepo->filterByPackageName($f3QueryWip, $packageName, 'f3');

    $f3QueryOuts = $this->f1f2OutRepo->getF3QueryByPackage($packageName);

    $queries = [
      'f1' => [
        'wip' => $f1QueryWip,
        'outs' => $f1QueryOuts,
      ],
      'f2' => [
        'wip' => $f2QueryWip,
        'outs' => $f2QueryOuts,
      ],
      'f3' => [
        'wip' => $f3QueryWip,
        'outs' => $f3QueryOuts,
      ],
    ];

    $results = [];

    foreach ($queries as $key => $queryPair) {
      foreach (['wip', 'outs'] as $type) {
        // foreach (['wip'] as $type) {
        if (!isset($queryPair[$type])) continue;

        $results["{$key}_{$type}"] = $this->applyTrendAggregation(
          $queryPair[$type],
          $period,
          $startDate,
          $endDate,
          "wip.Date_Loaded",
          ['SUM(wip.Qty)' => "{$type}_total_wip"],
          ['plref.production_line'],
          $workweek
        );
      }
    }

    Log::info($results['f1_wip']->toSql());

    $f1WipResults = $results['f1_wip']->get();
    $f2WipResults = $results['f2_wip']->get();
    $f3WipResults = $results['f3_wip']->get();

    $f1OutsResults = $results['f1_outs']->get();
    $f2OutsResults = $results['f2_outs']->get();
    $f3OutsResults = $results['f3_outs']->get();

    $groupingFieldsMap = [
      'weekly' => ['production_line', 'workweek'],
      'monthly' => ['production_line', 'year', 'month'],
      'quarterly' => ['production_line', 'year', 'quarter'],
      'yearly' => ['production_line', 'year'],
    ];

    $groupingFields = $groupingFieldsMap[$period] ?? ['production_line', 'day'];

    $overallResults = MergeAndAggregate::mergeAndAggregate(
      [
        $f1WipResults,
        $f2WipResults,
        $f3WipResults,
        $f1OutsResults,
        $f2OutsResults,
        $f3OutsResults
      ],
      $groupingFields
    );

    return response()->json([
      'status' => 'success',
      'message' => 'Highly optimized data retrieved successfully',
      'data' => [
        'F1_wip' => $f1WipResults,
        'F2_wip' => $f2WipResults,
        'F3_wip' => $f3WipResults,
        'F1_outs' => $f1OutsResults,
        'F2_outs' => $f2OutsResults,
        'F3_outs' => $f3OutsResults,
        'overall' => $overallResults,
      ],
    ]);
  }

  private function applyFilterRules(array $rules)
  {
    if (empty($rules)) {
      return null;
    }

    return function ($query) use ($rules) {
      if (isset($rules['lot_status_in'])) {
        $query->whereIn('Lot_Status', $rules['lot_status_in']);
      }

      if (isset($rules['lot_status_not_in'])) {
        $query->whereNotIn('Lot_Status', $rules['lot_status_not_in']);
      }

      if (isset($rules['stage_in'])) {
        $query->whereIn('Stage', $rules['stage_in']);
      }

      if (isset($rules['stage_not_in'])) {
        $query->whereNotIn('Stage', $rules['stage_not_in']);
      }

      if (isset($rules['station_not_in'])) {
        $query->whereNotIn('Station', $rules['station_not_in']);
      }

      if (isset($rules['station_in'])) {
        $query->whereIn('Station', $rules['station_in']);
      }

      if (isset($rules['stage_in_or_station_in'])) {
        $query->where(function ($q) use ($rules) {
          $q->whereIn('Stage', $rules['stage_in_or_station_in']['stage'])
            ->orWhereIn('Station', $rules['stage_in_or_station_in']['station']);
        });
      }

      if (isset($rules['stage_not_in_or_station_not_in'])) {
        $query->where(function ($q) use ($rules) {
          $q->whereNotIn('Stage', $rules['stage_not_in_or_station_not_in']['stage'])
            ->orWhereNotIn('Station', $rules['stage_not_in_or_station_not_in']['station']);
        });
      }

      if (isset($rules['is_for_bake'])) {
        if ($rules['is_for_bake'] === true) {
          $query->whereIn('Bake', ['For Bake'])
            ->where('Bake_Count', 0);
        } elseif ($rules['is_for_bake'] === false) {
          $query->where(function ($q) {
            $q->whereNull('Bake')
              ->orWhere('Bake', '')
              ->orWhere('Bake', '!=', 'For Bake')
              ->orWhereNotIn('Station', WipConstants::PRE_BAKE);
          });
        }
      }
    };
  }

  private function bakeConditionQuery(?string $filterType = null)
  {
    if (is_null($filterType)) {
      return null;
    }

    return function ($query) use ($filterType) {
      // Base bake query
      $query->whereIn('wip.Stage', ['TBAKEL'])
        ->whereNotIn('wip.Lot_Status', WipConstants::LOT_ON_HOLD)
        ->where(function ($q) {
          $q->whereNotIn('wip.Stage', WipConstants::BOXING_QA)
            ->orWhereNotIn('wip.Station', ['GTTFVI_T']);
        });

      // Additional filters based on type
      switch ($filterType) {
        case 'F2':
          $query->where(fn($q) => $this->f1f2WipRepo->applyF2Filters(
            $q,
            WipConstants::EWAN_PROCESS,
            'wip'
          ));
          $query->whereNotIn('wip.Station', [
            'GTTRES_T',
            'GTSUBCON',
            'GTARCH_T',
            'GTTBINLOC',
            ...WipConstants::LPI_LLI_BRAND_ERASE_SORT,
          ])->whereIn('wip.Station', WipConstants::PRE_BAKE);
          break;

        case 'F1':
          $query->whereNotIn('wip.Station', [
            ...WipConstants::LPI_LLI_BRAND_ERASE_SORT,
            ...WipConstants::PRE_BAKE
          ]);
          break;

        case 'PL1':
        case 'PL2':
          $query->whereNotIn('wip.Station', WipConstants::LPI_LLI_BRAND_ERASE_SORT)
            ->whereIn('wip.Station', WipConstants::PRE_BAKE);
          break;
      }
    };
  }

  public function getWIPStationSummaryTrend($packageName, $period, $startDate, $endDate, $workweeks, $filteringCondition)
  {
    $filterRules = $this->getFilterRules($filteringCondition);
    Log::info("Applying WIP Filter Summary Trend with condition: $filteringCondition");

    $trends = [];
    foreach (WipConstants::FACTORIES as $factory) {
      if ($factory === 'F3') {
        continue; // * NOTE: This Station is for F1/F2 only
      }

      $key = strtolower($factory) . '_trend';

      $query = $this->f1f2WipRepo->getTrend(
        $factory,
        $packageName,
        $period,
        $startDate,
        $endDate,
        $workweeks,
      );

      $query->where($this->applyFilterRules($filterRules));

      Log::info(SqlDebugHelper::prettify($query->toSql(), $query->getBindings()));

      if ($filteringCondition === 'Bake') {
        $bakeQuery = $this->f1f2WipRepo->getTrend(
          $factory,
          $packageName,
          $period,
          $startDate,
          $endDate,
          $workweeks,
        );
        // Log::info(SqlDebugHelper::prettify($query->toSql(), $query->getBindings()));
        // Log::info(SqlDebugHelper::prettify($bakeQuery->toSql(), $bakeQuery->getBindings()));
        $bakeQuery->where($this->bakeConditionQuery($filteringCondition));
        $query = $bakeQuery->unionAll($query);
      }

      $trends[$key] = $query->get();
    }

    $mergedTrends = WipTrendParser::parseTrendsByPeriod($trends);

    return response()->json(array_merge($trends, [
      'data' => $mergedTrends,
      'status' => 'success',
      'message' => 'Data retrieved successfully',
    ]));
  }

  private function getFilterRules($filteringCondition = null)
  {
    $rules = [
      'All' => [],
      'Processable' => [
        'lot_status_not_in' => WipConstants::LOT_ON_HOLD,
        'stage_not_in' => [...WipConstants::BOXING_QA, ...WipConstants::BAKE_REEL_TRANSFER],
        'station_not_in' => ['GTTFVI_T', 'GTTSORT_T', ...WipConstants::BRAND_ERASE],
        'is_for_bake' => false,
      ],
      'Hold' => [
        'lot_status_in' => WipConstants::LOT_ON_HOLD,
      ],
      'Pipeline' => [
        'lot_status_not_in' => WipConstants::LOT_ON_HOLD,
        'stage_in_or_station_in' => [
          'stage' => WipConstants::BOXING_QA,
          'station' => ['GTTFVI_T'],
        ],
      ],
      'Bake' => [
        'lot_status_not_in' => WipConstants::LOT_ON_HOLD,
        'is_for_bake' => true,
        'stage_not_in_or_station_not_in' => [
          'stage' => WipConstants::BOXING_QA,
          'station' => ['GTTFVI_T'],
        ],
        'stage_not_in' => WipConstants::BAKE_REEL_TRANSFER,
      ],
      'Detapesegregation' => [
        'lot_status_not_in' => WipConstants::LOT_ON_HOLD,
        'stage_not_in_or_station_not_in' => [
          'stage' => WipConstants::BOXING_QA,
          'station' => ['GTTFVI_T'],
        ],
        'stage_not_in' => ['TBAKEL'],
        'stage_in' => ['TDREEL', 'TRANSFER'],
      ],
      'Lpi' => [
        'lot_status_not_in' => WipConstants::LOT_ON_HOLD,
        'stage_not_in_or_station_not_in' => [
          'stage' => WipConstants::BOXING_QA,
          'station' => ['GTTFVI_T'],
        ],
        'stage_not_in' => WipConstants::BAKE_REEL_TRANSFER,
        'station_in' => ['GTLPI_T'],
      ],
      'Brand' => [
        'lot_status_not_in' => WipConstants::LOT_ON_HOLD,
        'stage_not_in_or_station_not_in' => [
          'stage' => WipConstants::BOXING_QA,
          'station' => ['GTTFVI_T'],
        ],
        'stage_not_in' => WipConstants::BAKE_REEL_TRANSFER,
        'station_not_in' => ['GTLPI_T'],
        'station_in' => WipConstants::BRAND_ERASE,
      ],
      'Lli' => [
        'lot_status_not_in' => WipConstants::LOT_ON_HOLD,
        'stage_not_in_or_station_not_in' => [
          'stage' => WipConstants::BOXING_QA,
          'station' => ['GTTFVI_T'],
        ],
        'stage_not_in' => WipConstants::BAKE_REEL_TRANSFER,
        'station_not_in' => ['GTLPI_T', ...WipConstants::BRAND_ERASE],
        'station_in' => ['GTTLLI_T'],
      ],
      'Sort' => [
        'lot_status_not_in' => WipConstants::LOT_ON_HOLD,
        'stage_not_in_or_station_not_in' => [
          'stage' => WipConstants::BOXING_QA,
          'station' => ['GTTFVI_T'],
        ],
        'stage_not_in' => WipConstants::BAKE_REEL_TRANSFER,
        'station_not_in' => ['GTLPI_T', ...WipConstants::BRAND_ERASE],
        'station_in' => ['GTTSORT_T'],
      ],
    ];

    if ($filteringCondition !== null) {
      return $rules[$filteringCondition] ?? [];
    }

    return $rules;
  }

  public function getOverallPickUp($startDate, $endDate)
  {
    // throw new Exception("test");
    $result = new \stdClass();

    $result->total_wip = $this->pickUpRepo->getTotalQuantity($startDate, $endDate);

    foreach (WipConstants::FACTORIES as $factory) {
      $key = strtolower($factory) . '_total_wip';
      $result->{$key} = $this->pickUpRepo->getFactoryTotalQuantityRanged($factory, $startDate, $endDate);
    }

    foreach (WipConstants::FACTORIES as $factory) {
      foreach (WipConstants::PRODUCTION_LINES as $pl) {
        $key = strtolower($factory) . strtolower(string: $pl) . '_total_wip';
        $result->{$key} = $this->pickUpRepo->getFactoryPlTotalQuantity($factory, $pl, $startDate, $endDate);
      }
    }

    $pl1_total_wip = (int) $result->f1pl1_total_wip + (int) $result->f2pl1_total_wip + (int) $result->f3pl1_total_wip;
    $pl6_total_wip = (int) $result->f1pl6_total_wip + (int) $result->f2pl6_total_wip + (int) $result->f3pl6_total_wip;

    return response()->json([
      'total_wip'    => (int) $result->total_wip,
      'f1_total_wip' => (int) $result->f1_total_wip,
      'f2_total_wip' => (int) $result->f2_total_wip,
      'f3_total_wip' => (int) $result->f3_total_wip,
      'total_f1_pl1'      => (int) $result->f1pl1_total_wip,
      'total_f1_pl6'      => (int) $result->f1pl6_total_wip,
      'total_f2_pl1'      => (int) $result->f2pl1_total_wip,
      'total_f2_pl6'      => (int) $result->f2pl6_total_wip,
      'total_f3_pl1'      => (int) $result->f3pl1_total_wip,
      'total_f3_pl6'      => (int) $result->f3pl6_total_wip,
      'total_pl1'         => $pl1_total_wip,
      'total_pl6'         => $pl6_total_wip,
      'status'            => 'success',
      'message'           => 'Data retrieved successfully',
    ]);
  }

  public function getPackagePickUpTrend($packageName, $period, $startDate, $endDate, $workweeks)
  {
    return $this->pickUpRepo->getPickUpTrend($packageName, $period, $startDate, $endDate, $workweeks);
  }

  public function getWipOutCapacitySummaryTrend($packageName, $period, $startDate, $endDate, $workweeks)
  {
    $trends = [];

    $prefixMap = [
      'f1_trend' => 'f1',
      'f2_trend' => 'f2',
      'f3_trend' => 'f3',
      'f1_capacity_trend' => 'f1',
      'f2_capacity_trend' => 'f2',
      'f3_capacity_trend' => 'f3',
      'overall_trend' => 'overall',
      'overall_capacity_trend' => 'overall'
    ];

    $selectColumns = ['wip.Date_Loaded as date_loaded', 'wip.Qty as qty', 'wip.Package_Name as package_name'];
    $aggregateColumnsF1 = WipConstants::FACTORY_AGGREGATES['F1']['wip']['wip'];
    $aggregateColumnsF2 = WipConstants::FACTORY_AGGREGATES['F2']['wip']['wip'];

    $trends['f1_trend'] = $this->f1f2WipRepo->getTrend('F1', $packageName, $period, $startDate, $endDate, workweeks: $workweeks, selectColumns: $selectColumns, aggregateColumns: $aggregateColumnsF1);
    // Log::info($trends['f1_trend']->toSql());
    Log::info("f1_trend query: " . SqlDebugHelper::prettify($trends['f1_trend']->toSql(), $trends['f1_trend']->getBindings()));

    $trends['f1_trend'] = $trends['f1_trend']->get();

    $trends['f2_trend'] = $this->f1f2WipRepo->getTrend('F2', $packageName, $period, $startDate, $endDate, workweeks: $workweeks, selectColumns: $selectColumns, aggregateColumns: $aggregateColumnsF2);
    // Log::info($trends['f2_trend']->toSql());
    Log::info("f2_trend query: " . SqlDebugHelper::prettify($trends['f2_trend']->toSql(), $trends['f2_trend']->getBindings()));

    $trends['f2_trend'] = $trends['f2_trend']->get();

    $trends['f3_trend'] = $this->f3WipRepo->getTrend($packageName, $period, $startDate, $endDate, $workweeks);

    $startDateCopy = is_object($startDate) ? clone $startDate : $startDate;
    foreach (WipConstants::FACTORIES as $factory) {
      // TODO capacity won't show if there's no wip data for the package in that factory
      // TODO cloning startDate works but it's a bit hacky, find a better way later
      $capacity = $this->capacityRepo->getCapacityTrend($packageName, $factory, $period, $startDateCopy, $endDate, $workweeks);
      $trends[strtolower($factory) . '_capacity_trend'] = $capacity;
    }

    Log::info("trends: " . json_encode($trends));

    $periodGroupBy = WipConstants::PERIOD_GROUP_BY[$period];
    $trends['overall_capacity_trend'] = MergeAndAggregate::mergeAndAggregate([$trends['f1_capacity_trend'], $trends['f2_capacity_trend'], $trends['f3_capacity_trend']], $periodGroupBy);
    $trends['overall_trend'] = MergeAndAggregate::mergeAndAggregate([$trends['f1_trend'], $trends['f2_trend'], $trends['f3_trend']], $periodGroupBy);

    // $mergedTrends = WipTrendParser::parseTrendsByPeriod($trends, periodFields: WipConstants::PERIOD_GROUP_BY[$period]);
    $mergedTrends = WipTrendParser::parseTrendsByPeriod($trends, $prefixMap);

    Log::info("mergedTrends: " . json_encode($mergedTrends));

    $f1f2out = $this->f1f2OutRepo->getOverallTrend($packageName, $period, $startDate, $endDate, $workweeks);
    $f3out = $this->f3OutRepo->getOverallTrend($packageName, $period, $startDate, $endDate, $workweeks);

    Log::info("getWipOutCapacitySummaryTrend: f1f2out: " . json_encode($f1f2out));
    Log::info("getWipOutCapacitySummaryTrend: f3out: " . json_encode($f3out));

    $merged = $this->mergeTrendsByKey('dateKey', ['label'], $mergedTrends, $f1f2out, $f3out);

    return response()->json([
      'data' => $merged,
      'status' => 'success',
      'message' => 'Data retrieved successfully'
    ]);
  }

  public function downloadAllFactoriesRawXlsx($packageName, $startDate, $endDate)
  {
    $spreadsheet = new Spreadsheet();

    // Fetch raw rows for each factory
    $f1Rows = $this->f1f2WipRepo->getTrend('F1', $packageName, null, $startDate, $endDate, workweeks: null, selectColumns: ['*'], aggregateColumns: false)->get();
    $f2Rows = $this->f1f2WipRepo->getTrend('F2', $packageName, null, $startDate, $endDate, workweeks: null, selectColumns: ['*'], aggregateColumns: false)->get();
    $f3Rows = $this->f3WipRepo->getTrend($packageName, null, $startDate, $endDate, null, false);

    // Log::info("Raw WIP by package: " . json_encode($f1Rows));
    // Log::info("Raw WIP by package: " . json_encode($f2Rows));
    // Log::info("Raw WIP by package: " . json_encode($f3Rows));

    // Helper to add a sheet
    $addSheet = function ($rows, $title, $spreadsheet, $sheetIndex) {
      if ($rows->isEmpty()) {
        return $sheetIndex;
      }

      $sheet = $sheetIndex === 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
      $sheet->setTitle($title);

      // Write headers
      $columns = array_keys((array) $rows->first());
      $sheet->fromArray($columns, null, 'A1');

      // Write data
      $data = $rows->map(fn($row) => (array) $row)->toArray();
      $sheet->fromArray($data, null, 'A2');

      return $sheetIndex + 1;
    };

    $sheetIndex = 0;
    $sheetIndex = $addSheet($f1Rows, 'F1', $spreadsheet, $sheetIndex);
    $sheetIndex = $addSheet($f2Rows, 'F2', $spreadsheet, $sheetIndex);
    $sheetIndex = $addSheet($f3Rows, 'F3', $spreadsheet, $sheetIndex);

    if ($sheetIndex === 0) {
      return response()->json([
        'status' => 'error',
        'message' => 'No data found for any factory.'
      ]);
    }

    $writer = new Xlsx($spreadsheet);
    // $spreadsheet = new Spreadsheet();
    // $writer = new Xlsx($spreadsheet);
    $writer->setPreCalculateFormulas(false);

    $fileName = "raw_trends_" . implode('_', $packageName) . "_" . now()->format('Ymd_His') . ".xlsx";

    $tempFile = tempnam(sys_get_temp_dir(), $fileName);
    $writer->save($tempFile);

    $headers = [
      'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    $response = response()->download($tempFile, $fileName, $headers);
    ob_end_clean();

    return $response->deleteFileAfterSend(true);
  }


  public function getPackagePickUpSummary($chartStatus, $startDate, $endDate)
  {
    $results = $this->pickUpRepo->getPackageSummary($chartStatus, $startDate, $endDate);

    return response()->json([
      'data' => $results,
      'status' => 'success',
      'message' => 'Data retrieved successfully'
    ]);
  }

  public function getAllPackages()
  {
    $f1f2Packages = $this->f1f2WipRepo->getDistinctPackages();
    // $fromPackageGroup = $this->packageGroupRepo->getUniquePackageNamesByFactory(['F1', 'F2', 'F3']);
    $f3Packages = $this->f3packageNamesRepo->getAllPackageNames();

    $all = array_values(array_unique(array_merge($f1f2Packages, WipConstants::SPECIAL_FILTER_VALUE, $f3Packages)));


    return array_map('strtoupper', $this->uniqueNormalized($all));
  }
}
