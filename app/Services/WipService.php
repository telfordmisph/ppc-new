<?php

namespace App\Services;

use App\Repositories\AnalogCalendarRepository;
use App\Repositories\WipRepository;
use App\Repositories\PickUpRepository;
use App\Helpers\SqlDebugHelper;
use App\Helpers\WipTrendParser;
use App\Constants\WipConstants;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Exception;

class WipService
{
  protected $analogCalendarRepository;
  protected $wipRepository;
  protected $pickUpRepository;
  private const F1F2_TABLE = "customer_data_wip";

  public function __construct(
    AnalogCalendarRepository $analogCalendarRepository,
    WipRepository $wipRepository,
    PickUpRepository $pickUpRepository
  ) {
    $this->analogCalendarRepository = $analogCalendarRepository;
    $this->wipRepository = $wipRepository;
    $this->pickUpRepository = $pickUpRepository;
  }

  private function applyDateOrWorkweekFilter($query, $alias, $useWorkweek, $workweek, $startDate = null, $endDate = null)
  {
    if ($useWorkweek) {
      $workweekArray = array_map('intval', explode(' ', $workweek));

      $weekRanges = $this->analogCalendarRepository->getWorkweekRanges($workweekArray);

      return $query->where(function ($q) use ($alias, $weekRanges) {
        foreach ($weekRanges as $range) {
          $q->orWhereBetween("$alias.Date_Loaded", [$range->start_date, $range->end_date]);
        }
      });
    }

    return $query->whereBetween("$alias.Date_Loaded", [$startDate, $endDate]);
  }

  public function getTodayWip()
  {
    $endDate = Carbon::now()->endOfDay();
    $startDate = Carbon::now()->subDays(25)->startOfDay();

    $f3DataRaw = $this->wipRepository->baseF3Query(false)
      ->selectRaw('DATE(Date_Loaded) AS report_date, SUM(Qty) AS f3_wip')
      ->whereBetween('Date_Loaded', [$startDate, $endDate])
      ->groupBy(DB::raw('DATE(Date_Loaded)'))
      ->get();

    $f3Data = [];
    foreach ($f3DataRaw as $row) {
      $f3Data[$row->report_date] = (int) $row->f3_wip;
    }

    $f1Data = DB::table(self::F1F2_TABLE . ' as wip')
      ->selectRaw('DATE(wip.Date_Loaded) AS report_date, SUM(wip.Qty) AS f1_wip')
      ->whereNotIn('wip.Focus_Group', ['DLT', 'WLT', 'SOF'])
      ->whereBetween('wip.Date_Loaded', [$startDate, $endDate]);

    $f1Data = $this->wipRepository->f1Filters(
      $f1Data,
      WipConstants::TODAY_WIP_INCLUDED_STATIONS,
      WipConstants::TODAY_WIP_EXCLUDED_STATIONS,
    )->groupBy(DB::raw('DATE(wip.Date_Loaded)'));

    $f2Data = DB::table(self::F1F2_TABLE . ' as wip')
      ->selectRaw('DATE(wip.Date_Loaded) AS report_date, SUM(wip.Qty) AS f2_wip');

    $f2Data = $this->wipRepository->applyF2Filters($f2Data, [...WipConstants::EWAN_PROCESS, 'Q-PITRANS1', 'GTTRANS_BE'], 'wip')
      ->whereBetween('wip.Date_Loaded', [$startDate, $endDate])
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
  }

  public function getOverallWip($startDate, $endDate, $useWorkweek, $workweek)
  {
    $f1Query = DB::table(self::F1F2_TABLE . ' as wip')
      ->selectRaw('SUM(wip.Qty) AS f1_total_quantity');
    $f1Query = $this->wipRepository->f1Filters($f1Query, ['GTREEL'], WipConstants::REEL_EXCLUDED_STATIONS_F1_OVERALL, 'wip');

    $f1Query = $this->applyDateOrWorkweekFilter($f1Query, 'wip', $useWorkweek, $workweek, $startDate, $endDate);
    $f1QueryResult = $f1Query->first();

    $f2Query = DB::table(self::F1F2_TABLE . ' as wip')
      ->selectRaw('SUM(wip.Qty) AS f2_total_quantity');
    $f2Query = $this->wipRepository->applyF2Filters($f2Query, WipConstants::EXCLUDED_STATIONS_F2, 'wip');

    $f2Query = $this->applyDateOrWorkweekFilter($f2Query, 'wip', $useWorkweek, $workweek, $startDate, $endDate);
    $f2QueryResult = $f2Query->first();

    $f1pl6Query = DB::table(self::F1F2_TABLE . ' as wip')
      ->selectRaw('SUM(wip.Qty) AS f1pl6_total_quantity');
    $f1pl6Query = $this->wipRepository->f1Filters($f1pl6Query, ['GTREEL'], WipConstants::REEL_EXCLUDED_STATIONS_F1_PL, 'wip');
    $f1pl6Query = $this->wipRepository->joinPL($f1pl6Query, WipConstants::SPECIAL_PART_NAMES, 'PL6');

    $f1pl6Query = $this->applyDateOrWorkweekFilter($f1pl6Query, 'wip', $useWorkweek, $workweek, $startDate, $endDate);
    $f1pl6QueryResult = $f1pl6Query->first();

    $f1pl1Query = DB::table(self::F1F2_TABLE . ' as wip')
      ->selectRaw('SUM(wip.Qty) AS f1pl1_total_quantity');
    $f1pl1Query = $this->wipRepository->f1Filters($f1pl1Query, ['GTREEL'], WipConstants::REEL_EXCLUDED_STATIONS_F1_PL, 'wip');
    $f1pl1Query = $this->wipRepository->joinPL($f1pl1Query, WipConstants::SPECIAL_PART_NAMES, 'PL1');

    $f1pl1Query = $this->applyDateOrWorkweekFilter($f1pl1Query, 'wip', $useWorkweek, $workweek, $startDate, $endDate);
    $f1pl1QueryResult = $f1pl1Query->first();

    $f2pl6Query = DB::table(self::F1F2_TABLE . ' as wip')
      ->selectRaw('SUM(wip.Qty) AS f2pl6_total_quantity');
    $f2pl6Query = $this->wipRepository->applyF2Filters($f2pl6Query, WipConstants::EWAN_PROCESS, 'wip');
    $f2pl6Query = $this->wipRepository->joinPL($f2pl6Query, WipConstants::SPECIAL_PART_NAMES, 'PL6');

    $f2pl6Query = $this->applyDateOrWorkweekFilter($f2pl6Query, 'wip', $useWorkweek, $workweek, $startDate, $endDate);
    $f2pl6QueryResult = $f2pl6Query->first();

    $f2pl1Query = DB::table(self::F1F2_TABLE . ' as wip')
      ->selectRaw('SUM(wip.Qty) AS f2pl1_total_quantity');
    $f2pl1Query = $this->wipRepository->applyF2Filters($f2pl1Query, WipConstants::EXCLUDED_STATIONS_F2, 'wip');
    $f2pl1Query = $this->wipRepository->joinPL($f2pl1Query, WipConstants::SPECIAL_PART_NAMES, 'PL1');

    $f2pl1Query = $this->applyDateOrWorkweekFilter($f2pl1Query, 'wip', $useWorkweek, $workweek, $startDate, $endDate);
    $f2pl1QueryResult = $f2pl1Query->first();

    $f3Total = $this->wipRepository->baseF3Query()
      ->selectRaw("SUM(wip.Qty) AS f3_total_quantity");

    $f3Total = $this->applyDateOrWorkweekFilter($f3Total, 'wip', $useWorkweek, $workweek, $startDate, $endDate)
      ->first();

    $f3PlTotals = $this->wipRepository->baseF3Query(true)
      ->selectRaw("
          SUM(CASE WHEN plref.production_line = 'PL1' THEN wip.Qty ELSE 0 END) AS f3pl1_total_quantity,
          SUM(CASE WHEN plref.production_line = 'PL6' THEN wip.Qty ELSE 0 END) AS f3pl6_total_quantity
      ");

    $f3PlTotals = $this->applyDateOrWorkweekFilter($f3PlTotals, 'wip', $useWorkweek, $workweek, $startDate, $endDate)
      ->first();

    $f3TotalQty = (int) $f3Total->f3_total_quantity;

    $f1Total = (int) ($f1QueryResult->f1_total_quantity ?? 0);
    $f2Total = (int) ($f2QueryResult->f2_total_quantity ?? 0);

    $grandTotal = $f1Total + $f2Total + $f3TotalQty;

    return response()->json([
      'f1_total_quantity' => $f1Total,
      'f2_total_quantity' => $f2Total,
      'f3_total_quantity' => $f3TotalQty,
      'total_f1_pl1' => (int) $f1pl1QueryResult->f1pl1_total_quantity,
      'total_f1_pl6' => (int) $f1pl6QueryResult->f1pl6_total_quantity,
      'total_f2_pl1' => (int) $f2pl1QueryResult->f2pl1_total_quantity,
      'total_f2_pl6' => (int) $f2pl6QueryResult->f2pl6_total_quantity,
      'total_f3_pl1' => (int) $f3PlTotals->f3pl1_total_quantity,
      'total_f3_pl6' => (int) $f3PlTotals->f3pl6_total_quantity,
      'total_quantity' => $grandTotal,
      'status' => 'success',
      'message' => 'Data retrieved successfully'
    ]);
  }

  private function getFactoryTrend($factory, $packageName, $period, $lookBack, $offsetDays)
  {
    // TODO: consider mapping instead of switch
    switch ($factory) {
      case 'F1':
        $query = DB::table(self::F1F2_TABLE . ' as wip');
        $query = $this->wipRepository->f1Filters($query, WipConstants::REEL_TRANSFER_B3, WipConstants::REEL_TRANSFER_EXCLUDED_STATIONS_F1, 'wip');
        break;

      case 'F2':
        $query = DB::table(self::F1F2_TABLE . ' as wip');
        $query = $this->wipRepository->applyF2Filters($query, WipConstants::EWAN_PROCESS, 'wip');
        break;

      case 'PL1':
      case 'PL6':
        $query = DB::table(self::F1F2_TABLE . ' as wip');
        $query = $this->wipRepository->joinPL($query, WipConstants::SPECIAL_PART_NAMES, $factory);
        $query->where(function ($sub) {
          $this->wipRepository->f1Filters($sub, WipConstants::REEL_TRANSFER_B3, WipConstants::REEL_TRANSFER_EXCLUDED_STATIONS_F1, 'wip');

          $sub->orWhere(function ($f2) {
            $f2->where(fn($q) => $this->wipRepository->applyF2Filters($q, WipConstants::EWAN_PROCESS, 'wip'));
          });
        });
        break;

      case 'F3':
        $query = $this->wipRepository->baseF3Query();
        break;

      default:
        break;
    }

    $query = $this->wipRepository->filterByPackageName($query, $packageName);
    $query = $this->wipRepository->getTrend($query, $period, $lookBack, $offsetDays);

    return $query;
  }

  public function getOverallWipByPackage($startDate, $endDate, $useWorkweek, $workweek, $packageName, $period, $lookBack, $offsetDays)
  {
    $trends = [];

    foreach (WipConstants::FACTORIES as $factory) {
      $key = strtolower($factory) . '_trend';
      $trends[$key] = $this->getFactoryTrend($factory, $packageName, $period, $lookBack, $offsetDays)->get();
    }

    $mergedTrends = WipTrendParser::parseTrendsByPeriod($trends);

    return response()->json(array_merge($trends, [
      'data' => $mergedTrends,
      'status' => 'success',
      'message' => 'Data retrieved successfully',
    ]));
  }

  public function getOverallResidual($startDate, $endDate)
  {
    $result = new \stdClass();

    $fBaseQuery = DB::table(self::F1F2_TABLE . ' as wip')
      ->whereBetween('wip.Date_Loaded', [$startDate, $endDate])
      ->where('wip.Station', 'GTTRES_T');

    $result->f1_total_quantity = (clone $fBaseQuery)
      ->where(fn($q) => $this->wipRepository->applyF1Filters($q, WipConstants::F1_EXCLUDED_PLANT, 'wip'))
      ->sum('QTY');

    $result->f2_total_quantity = (clone $fBaseQuery)
      ->where(fn($q) => $this->wipRepository->applyF2Filters($q, [], 'wip'))
      ->sum('QTY');

    $result->total_quantity = $result->f1_total_quantity + $result->f2_total_quantity;

    $result->f1pl1_total_quantity = $this->wipRepository->joinPL((clone $fBaseQuery), WipConstants::SPECIAL_PART_NAMES, 'PL1')
      ->where(fn($q) => $this->wipRepository->applyF1Filters($q, WipConstants::F1_EXCLUDED_PLANT, 'wip'))
      ->sum('Qty');

    $result->f2pl1_total_quantity = $this->wipRepository->joinPL((clone $fBaseQuery), WipConstants::SPECIAL_PART_NAMES, 'PL1')
      ->where(fn($q) => $this->wipRepository->applyF2Filters($q, [], 'wip'))
      ->sum('Qty');

    $result->f1pl6_total_quantity = $this->wipRepository->joinPL((clone $fBaseQuery), WipConstants::SPECIAL_PART_NAMES, 'PL6')
      ->where(fn($q) => $this->wipRepository->applyF1Filters($q, WipConstants::F1_EXCLUDED_PLANT, null))
      ->sum('Qty');

    $result->f2pl6_total_quantity = $this->wipRepository->joinPL((clone $fBaseQuery), WipConstants::SPECIAL_PART_NAMES, 'PL6')
      ->where(fn($q) => $this->wipRepository->applyF2Filters($q, [], 'wip'))
      ->sum('Qty');


    return response()->json([
      'total_quantity' => (int) $result->total_quantity,
      'f1_total_quantity' => (int) $result->f1_total_quantity,
      'f2_total_quantity' => (int) $result->f2_total_quantity,
      'total_f1_pl1' => (int) $result->f1pl1_total_quantity,
      'total_f1_pl6' => (int) $result->f1pl6_total_quantity,
      'total_f2_pl1' => (int) $result->f2pl1_total_quantity,
      'total_f2_pl6' => (int) $result->f2pl6_total_quantity,
      'total_pl1' => (int) $result->f1pl1_total_quantity + (int) $result->f2pl1_total_quantity,
      'total_pl6' => (int) $result->f1pl6_total_quantity + (int) $result->f2pl6_total_quantity,
      'status' => 'success',
      'message' => 'Data retrieved successfully'
    ]);
  }

  public function getPackageResidualSummary($chartStatus, $startDate, $endDate)
  {
    switch ($chartStatus) {
      case 'all':
        $query = DB::table(self::F1F2_TABLE . ' as wip')
          ->selectRaw('wip.Package_Name AS PACKAGE, SUM(Qty) AS total_quantity, COUNT(DISTINCT Lot_Id) AS total_lots')
          ->where('wip.Station', 'GTTRES_T')
          ->whereBetween('wip.Date_Loaded', [$startDate, $endDate]);

        if ($chartStatus === 'all') {
          $query->where(function ($q) {
            $q->where(function ($sub) {
              $sub->where(fn($q) => $this->wipRepository->applyF1Filters($q, WipConstants::F1_EXCLUDED_PLANT, 'wip'));
            })
              ->orWhere(function ($sub) {
                $sub->where(fn($q) => $this->wipRepository->applyF2Filters($q, [], 'wip'));
              });
          });
        }

        $query->groupBy('PACKAGE')
          ->orderByDesc('total_quantity');
        break;

      case 'F1':
        $query = DB::table(self::F1F2_TABLE . ' as wip')
          ->selectRaw('wip.Package_Name AS PACKAGE, SUM(Qty) AS total_quantity, COUNT(DISTINCT Lot_Id) AS total_lots')
          ->where('wip.Station', 'GTTRES_T')
          ->whereBetween('wip.Date_Loaded', [$startDate, $endDate]);
        $query->where(fn($q) => $this->wipRepository->applyF1Filters($q, WipConstants::F1_EXCLUDED_PLANT, 'wip'));

        $query->groupBy('PACKAGE')
          ->orderByDesc('total_quantity');
        break;

      case 'F2':
      case 'F3':
      case 'PL1':
      case 'PL6':
        $query = DB::table('ppc_pickupdb')
          ->selectRaw('ppc_pickupdb.PACKAGE AS PACKAGE, SUM(QTY) AS total_quantity, COUNT(DISTINCT LOTID) AS total_lots')
          ->join('ppc_partnamedb as partname', 'ppc_pickupdb.PARTNAME', '=', 'partname.Partname')
          ->whereBetween('ppc_pickupdb.DATE_CREATED', [$startDate, $endDate]);

        if ($chartStatus === 'F2' || $chartStatus === 'F3') {
          $query->where('partname.Factory', strtoupper($chartStatus));
        } elseif ($chartStatus === 'PL1' || $chartStatus === 'PL6') {
          $query->where('partname.PL', $chartStatus);
        }

        $query->groupBy('PACKAGE')
          ->orderByDesc('total_quantity');
        break;

      default:
        $query = DB::table('ppc_pickupdb')
          ->selectRaw('PACKAGE AS PACKAGE, SUM(QTY) AS total_quantity, COUNT(DISTINCT LOTID) AS total_lots')
          ->whereDate('DATE_CREATED', now())
          ->groupBy('PACKAGE')
          ->orderByDesc('total_quantity');
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
    Log::info("useWorkweek: $useWorkweek, workweek: $workweek, startDate: $startDate, endDate: $endDate, includePL: $includePL");
    // -----------------------------
    //  BASE QUERY SETUP
    // -----------------------------
    $baseQuery = DB::table(self::F1F2_TABLE . ' as wip')
      ->selectRaw(
        $includePL
          ? 'wip.Package_Name, plref.production_line as PL, SUM(wip.Qty) AS total_quantity, COUNT(DISTINCT wip.Lot_Id) as total_lots'
          : 'wip.Package_Name, SUM(wip.Qty) AS total_quantity, COUNT(DISTINCT wip.Lot_Id) as total_lots'
      );

    if ($includePL) {
      $baseQuery = $this->wipRepository->joinPL($baseQuery);
    }

    $baseQuery = $this->applyDateOrWorkweekFilter($baseQuery, 'wip', $useWorkweek, $workweek, $startDate, $endDate);
    $baseQuery = $baseQuery->groupBy($includePL ? ['Package_Name', 'plref.production_line'] : ['Package_Name']);

    // -----------------------------
    //  F1 QUERIES
    // -----------------------------
    $f1Query = (clone $baseQuery)
      ->where(fn($q) => $this->wipRepository->f1Filters($q, WipConstants::REEL_TRANSFER_B3, WipConstants::REEL_TRANSFER_EXCLUDED_STATIONS_F1, 'wip'));

    // -----------------------------
    //  F2 QUERIES
    // -----------------------------
    $f2Query = (clone $baseQuery)
      ->where(fn($q) => $this->wipRepository->applyF2Filters($q, WipConstants::EWAN_PROCESS, 'wip'));

    // -----------------------------
    //  F3 QUERIES
    // -----------------------------
    $f3Query = $this->wipRepository->baseF3Query($includePL);
    $f3Query = $this->applyDateOrWorkweekFilter($f3Query, 'wip', $useWorkweek, $workweek, $startDate, $endDate);
    $f3Query = $f3Query->selectRaw(
      $includePL
        ? 'wip.Package_Name, plref.production_line as PL, SUM(wip.Qty) AS total_quantity, COUNT(DISTINCT wip.Lot_Id) AS total_lots'
        : 'wip.Package_Name, SUM(wip.Qty) AS total_quantity, COUNT(DISTINCT wip.Lot_Id) AS total_lots'
    );
    if ($includePL) {
      $f3Query->groupBy('wip.Package_Name', 'plref.production_line');
    } else {
      $f3Query->groupBy('wip.Package_Name');
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
        DB::raw('COALESCE(f1.total_quantity, 0) as f1_total_quantity'),
        DB::raw('COALESCE(f2.total_quantity, 0) as f2_total_quantity'),
        DB::raw('COALESCE(f3.total_quantity, 0) as f3_total_quantity'),
        DB::raw('COALESCE(f1.total_lots, 0) as f1_total_lots'),
        DB::raw('COALESCE(f2.total_lots, 0) as f2_total_lots'),
        DB::raw('COALESCE(f3.total_lots, 0) as f3_total_lots')
      );

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
        DB::raw('COALESCE(f1.total_quantity, 0) as f1_total_quantity'),
        DB::raw('COALESCE(f2.total_quantity, 0) as f2_total_quantity'),
        DB::raw('COALESCE(f3.total_quantity, 0) as f3_total_quantity'),
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
        DB::raw('COALESCE(f1.total_quantity, 0) as f1_total_quantity'),
        DB::raw('COALESCE(f2.total_quantity, 0) as f2_total_quantity'),
        DB::raw('COALESCE(f3.total_quantity, 0) as f3_total_quantity'),
        DB::raw('COALESCE(f1.total_lots, 0) as f1_total_lots'),
        DB::raw('COALESCE(f2.total_lots, 0) as f2_total_lots'),
        DB::raw('COALESCE(f3.total_lots, 0) as f3_total_lots')
      );

    $lfcspQuery = DB::table(self::F1F2_TABLE . ' as wip')
      ->selectRaw(
        $includePL
          ? "'LFCSP' AS Package_Name, 'PL1' AS PL, SUM(wip.Qty) AS total_quantity, COUNT(DISTINCT wip.Lot_Id) AS total_lots"
          : "'LFCSP' AS Package_Name, SUM(wip.Qty) AS total_quantity, COUNT(DISTINCT wip.Lot_Id) AS total_lots"
      )
      ->whereIn('wip.Part_Name', WipConstants::SPECIAL_PART_NAMES);
    $lfcspQuery = $this->wipRepository->f1Filters($lfcspQuery, WipConstants::REEL_TRANSFER_B3, WipConstants::REEL_TRANSFER_EXCLUDED_STATIONS_F1, 'wip');

    $lfcspQuery = $lfcspQuery->groupBy($includePL ? [DB::raw("'LFCSP'"), DB::raw("'PL1'")] : [DB::raw("'LFCSP'")]);
    // ->orderByDesc('total_quantity');
    $lfcspQuery = $this->applyDateOrWorkweekFilter($lfcspQuery, 'wip', $useWorkweek, $workweek, $startDate, $endDate);

    $lfcspLeft = DB::table(DB::raw("({$lfcspQuery->toSql()}) as f1"))
      ->mergeBindings($lfcspQuery)
      ->leftJoin(DB::raw("({$f2Query->toSql()}) as f2"), fn($join) => $joinCondition($join, 'f1', 'f2'))
      ->mergeBindings($f2Query)
      ->leftJoin(DB::raw("({$f3Query->toSql()}) as f3"), fn($join) => $joinCondition($join, 'f1', 'f3'))
      ->mergeBindings($f3Query)
      ->select(
        'f1.Package_Name as Package_Name',
        $includePL ? 'f1.PL as PL' : DB::raw("'N/A' as PL"),
        DB::raw('COALESCE(f1.total_quantity, 0) as f1_total_quantity'),
        DB::raw('COALESCE(f2.total_quantity, 0) as f2_total_quantity'),
        DB::raw('COALESCE(f3.total_quantity, 0) as f3_total_quantity'),
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
        DB::raw('MAX(f1_total_quantity) as f1_total_quantity'),
        DB::raw('MAX(f2_total_quantity) as f2_total_quantity'),
        DB::raw('MAX(f3_total_quantity) as f3_total_quantity'),
        DB::raw('(MAX(f1_total_quantity) + MAX(f2_total_quantity) + MAX(f3_total_quantity)) as total_quantity'),
        DB::raw('MAX(f1_total_lots) as f1_total_lots'),
        DB::raw('MAX(f2_total_lots) as f2_total_lots'),
        DB::raw('MAX(f3_total_lots) as f3_total_lots'),
        DB::raw('(MAX(f1_total_lots) + MAX(f2_total_lots) + MAX(f3_total_lots)) as total_lots')
      )
      ->groupBy($includePL ? ['Package_Name', 'PL'] : ['Package_Name'])
      ->orderByDesc('total_quantity')
      ->get();

    $totalQuantity = $results->sum('total_quantity');

    // =====================================================================
    //  RESPONSE
    // =====================================================================
    return response()->json([
      'status' => 'success',
      'message' => 'Highly optimized data retrieved successfully',
      'total_quantity' => $totalQuantity,
      'data' => $results,
    ]);
  }


  private function applyFilterRules($query, $rules): Builder
  {
    if (!$rules) return $query;

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
        $query
          ->whereIn('wip.Bake', ['For Bake'])
          ->where('wip.Bake_Count', 0);
      } elseif ($rules['is_for_bake'] === false) {
        $query->where(function ($q) {
          $q->whereNull('Bake')
            ->orWhere('Bake', '')
            ->orWhere('Bake', '!=', 'For Bake')
            ->orWhereNotIn('Station', WipConstants::PRE_BAKE);
        });
      }
    }

    return $query;
  }

  private function bakeConditionQuery($query, $filterType = null)
  {
    $mainBakeQuery = DB::table(self::F1F2_TABLE . ' as wip')
      // ->whereBetween('wip.Date_Loaded', [$startOfDay, $endOfDay])
      ->whereIn('wip.Stage', ['TBAKEL'])
      ->whereNotIn('wip.Lot_Status', WipConstants::LOT_ON_HOLD)
      ->where(function ($q) {
        $q->whereNotIn('wip.Stage', WipConstants::BOXING_QA)
          ->orWhereNotIn('wip.Station', ['GTTFVI_T']);
      });

    switch ($filterType) {
      case 'F2':
        $mainBakeQuery->where(fn($q) => $this->wipRepository->applyF2Filters($q, WipConstants::EWAN_PROCESS, 'wip'));

        $query->whereNotIn('Station', [
          // todo: missing GTOUT ?? really ???
          'GTTRES_T',
          'GTSUBCON',
          'GTARCH_T',
          'GTTBINLOC',
          ...WipConstants::LPI_LLI_BRAND_ERASE_SORT,
        ])->whereIn('Station', WipConstants::PRE_BAKE);
        break;

      case 'F1':
        $query->whereNotIn('Station', [
          ...WipConstants::LPI_LLI_BRAND_ERASE_SORT,
          ...WipConstants::PRE_BAKE
        ]);
        break;

      case 'PL1':
      case 'PL2':
        $query->whereNotIn('Station', WipConstants::LPI_LLI_BRAND_ERASE_SORT)->whereIn('Station', WipConstants::PRE_BAKE);
        break;
    }

    return $mainBakeQuery;
  }

  public function getWIPFilterSummaryTrend($packageName, $period, $lookBack, $offsetDays, $filteringCondition)
  {
    $filterRules = $this->getFilterRules($filteringCondition);
    Log::info("Applying WIP Filter Summary Trend with condition: $filteringCondition");

    $trends = [];
    foreach (WipConstants::FACTORIES as $factory) {
      $key = strtolower($factory) . '_trend';

      $query = $this->getFactoryTrend($factory, $packageName, $period, $lookBack, $offsetDays);
      $query = $this->applyFilterRules($query, $filterRules);

      $query = $this->wipRepository->filterByPackageName($query, $packageName);
      // $query = $this->wipRepository->getTrend($query, $period, $lookBack, $offsetDays);

      if ($filteringCondition === 'Bake') {
        $bakeQuery = $this->bakeConditionQuery($query);
        $bakeQuery = $this->wipRepository->filterByPackageName($bakeQuery, $packageName);
        $bakeQuery = $this->wipRepository->getTrend($bakeQuery, $period, $lookBack, $offsetDays);
        Log::info("===== WIP FILTER SUMMARY TREND QUERY =====");
        // Log::info(SqlDebugHelper::prettify($query->toSql(), $query->getBindings()));
        // Log::info(SqlDebugHelper::prettify($bakeQuery->toSql(), $bakeQuery->getBindings()));
        $query = $bakeQuery->unionAll($query);
      }

      Log::info(SqlDebugHelper::prettify($query->toSql(), $query->getBindings()));

      $trends[$key] = $query->get();
    }

    $mergedTrends = WipTrendParser::parseTrendsByPeriod($trends);

    // return response()->json(array_merge($trends, [
    //   'status' => 'success',
    //   'message' => 'Data retrieved successfully',
    // ]));
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

    $result->total_quantity = $this->pickUpRepository->getTotalQuantity($startDate, $endDate);

    foreach (WipConstants::FACTORIES as $factory) {
      $key = strtolower($factory) . '_total_quantity';
      $result->{$key} = $this->pickUpRepository->getFactoryTotalQuantityRanged($factory, $startDate, $endDate);
    }

    foreach (WipConstants::FACTORIES as $factory) {
      foreach (WipConstants::PRODUCTION_LINES as $pl) {
        $key = strtolower($factory) . strtolower($pl) . '_total_quantity';
        $result->{$key} = $this->pickUpRepository->getFactoryPlTotalQuantity($factory, $pl, $startDate, $endDate);
      }
    }

    $pl1_total_quantity = (int) $result->f1pl1_total_quantity + (int) $result->f2pl1_total_quantity + (int) $result->f3pl1_total_quantity;
    $pl6_total_quantity = (int) $result->f1pl6_total_quantity + (int) $result->f2pl6_total_quantity + (int) $result->f3pl6_total_quantity;

    return response()->json([
      'total_quantity'    => (int) $result->total_quantity,
      'f1_total_quantity' => (int) $result->f1_total_quantity,
      'f2_total_quantity' => (int) $result->f2_total_quantity,
      'f3_total_quantity' => (int) $result->f3_total_quantity,
      'total_f1_pl1'      => (int) $result->f1pl1_total_quantity,
      'total_f1_pl6'      => (int) $result->f1pl6_total_quantity,
      'total_f2_pl1'      => (int) $result->f2pl1_total_quantity,
      'total_f2_pl6'      => (int) $result->f2pl6_total_quantity,
      'total_f3_pl1'      => (int) $result->f3pl1_total_quantity,
      'total_f3_pl6'      => (int) $result->f3pl6_total_quantity,
      'total_pl1'         => $pl1_total_quantity,
      'total_pl6'         => $pl6_total_quantity,
      'status'            => 'success',
      'message'           => 'Data retrieved successfully',
    ]);
  }

  public function getPackagePickUpTrend($packageName, $period, $lookBack, $offsetDays)
  {
    return $this->pickUpRepository->getPickUpTrend($packageName, $period, $lookBack, $offsetDays);
  }

  public function getPackagePickUpSummary($chartStatus, $startDate, $endDate)
  {
    $results = $this->pickUpRepository->getPackageSummary($chartStatus, $startDate, $endDate);

    return response()->json([
      'data' => $results,
      'status' => 'success',
      'message' => 'Data retrieved successfully'
    ]);
  }
}
