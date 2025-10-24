<?php

namespace App\Services;

use App\Repositories\AnalogCalendarRepository;
use App\Repositories\WipRepository;
use App\Repositories\PickUpRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;
use App\Constants\WipConstants;
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
    $startDate = Carbon::now()->subDays(20)->startOfDay();

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
      ->where(fn($q) => $this->wipRepository->applyF1Filters($q, WipConstants::F1_EXCLUDED_PLANT, null))
      ->whereNotIn('wip.Focus_Group', ['DLT', 'WLT', 'SOF'])
      ->whereBetween('wip.Date_Loaded', [$startDate, $endDate]);

    $f1Data = $this->wipRepository->applyStationFilter(
      $f1Data,
      [
        'GTTRES_T',
        'GTREEL',
        'CVDTRAN_GT',
        'GTARCH_T',
        'GTTRANS_BE',
        'PITBOX_T',
        'PITBOX1',
        'PITFVI1',
        'PITLABEL1',
        'PITOQA',
        'PITOQA1',
        'Q-PITRANS1'
      ],
      [
        'GTSUBCON',
        'GTGOUT',
        'GTTBINLOC',
        ...WipConstants::BRAND_TRANSFER_B3,
        ...WipConstants::TRANSFER_QA,
        ...WipConstants::FINAL_QA_STATION
      ]
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
      ->selectRaw('SUM(wip.Qty) AS f1_total_quantity')
      ->where(fn($q) => $this->wipRepository->applyF1Filters($q, WipConstants::F1_EXCLUDED_PLANT, 'wip'));

    $f1Query = $this->wipRepository->applyStationFilter($f1Query, ['GTREEL'], WipConstants::REEL_EXCLUDED_STATIONS_F1_OVERALL);
    $f1Query = $this->applyDateOrWorkweekFilter($f1Query, 'wip', $useWorkweek, $workweek, $startDate, $endDate);
    $f1QueryResult = $f1Query->first();

    $f2Query = DB::table(self::F1F2_TABLE . ' as wip')
      ->selectRaw('SUM(wip.Qty) AS f2_total_quantity');
    $f2Query = $this->wipRepository->applyF2Filters($f2Query, WipConstants::EXCLUDED_STATIONS_F2, 'wip');
    $f2Query = $this->applyDateOrWorkweekFilter($f2Query, 'wip', $useWorkweek, $workweek, $startDate, $endDate);
    $f2QueryResult = $f2Query->first();

    $f1pl6Query = DB::table(self::F1F2_TABLE . ' as wip')
      ->selectRaw('SUM(wip.Qty) AS f1pl6_total_quantity')
      ->where(fn($q) => $this->wipRepository->applyF1Filters($q, WipConstants::F1_EXCLUDED_PLANT, 'wip'));
    $f1pl6Query = $this->wipRepository->joinPL($f1pl6Query, WipConstants::SPECIAL_PART_NAMES, 'PL6');
    $f1pl6Query = $this->wipRepository->applyStationFilter($f1pl6Query, ['GTREEL'], WipConstants::REEL_EXCLUDED_STATIONS_F1_PL);
    $f1pl6Query = $this->applyDateOrWorkweekFilter($f1pl6Query, 'wip', $useWorkweek, $workweek, $startDate, $endDate);
    $f1pl6QueryResult = $f1pl6Query->first();

    $f1pl1Query = DB::table(self::F1F2_TABLE . ' as wip')
      ->selectRaw('SUM(wip.Qty) AS f1pl1_total_quantity')
      ->where(fn($q) => $this->wipRepository->applyF1Filters($q, WipConstants::F1_EXCLUDED_PLANT, 'wip'));
    $f1pl1Query = $this->wipRepository->joinPL($f1pl1Query, WipConstants::SPECIAL_PART_NAMES, 'PL1');
    $f1pl1Query = $this->wipRepository->applyStationFilter($f1pl1Query, ['GTREEL'], WipConstants::REEL_EXCLUDED_STATIONS_F1_PL);
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

  public function getWIPQuantityAndLotsTotal($useWorkweek, $workweek, $startDate = null, $endDate = null)
  {
    $baseQuery = DB::table(self::F1F2_TABLE . ' as wip')
      ->selectRaw('wip.Package_Name, SUM(wip.Qty) AS total_quantity, Count(DISTINCT wip.Lot_Id) as total_lots');
    $baseQuery = $this->applyDateOrWorkweekFilter($baseQuery, 'wip', $useWorkweek, $workweek, $startDate, $endDate);

    $f1Base = (clone $baseQuery)
      ->where(fn($q) => $this->wipRepository->applyF1Filters($q, WipConstants::F1_EXCLUDED_PLANT, 'wip'));
    $f1Base = $this->wipRepository->applyStationFilter($f1Base, WipConstants::REEL_TRANSFER_B3, WipConstants::REEL_TRANSFER_EXCLUDED_STATIONS_F1);

    $f1Query = (clone $f1Base)
      ->groupBy('wip.Package_Name');

    $f1PLQuery = (clone $f1Base)
      ->selectRaw('plref.production_line as PL');
    $f1PLQuery = $this->wipRepository->joinPL($f1PLQuery)
      ->whereNotIn('wip.Part_Name', WipConstants::SPECIAL_PART_NAMES)
      ->groupBy('wip.Package_Name', 'plref.production_line');

    $f2Base = (clone $baseQuery)
      ->where(fn($q) => $this->wipRepository->applyF2Filters($q, WipConstants::EWAN_PROCESS, 'wip'));

    $f2Query = (clone $f2Base)
      ->groupBy('wip.Package_Name');

    $f2PLQuery = (clone $f2Base)
      ->selectRaw('plref.production_line as PL');
    $f2PLQuery = $this->wipRepository->joinPL($f2PLQuery)
      ->whereNotIn('wip.Part_Name', WipConstants::SPECIAL_PART_NAMES)
      ->groupBy('wip.Package_Name', 'plref.production_line');

    $baseF3Query = $this->wipRepository->baseF3Query(true);
    $baseF3Query = $this->applyDateOrWorkweekFilter($baseF3Query,  'wip',  $useWorkweek,  $workweek,  $startDate,  $endDate);

    $f3Query = (clone $baseF3Query)
      ->selectRaw('wip.Package_Name, SUM(wip.Qty) AS total_quantity, COUNT(DISTINCT wip.Lot_Id) AS total_lots')
      ->groupBy('wip.Package_Name');

    $f3PLQuery = (clone $baseF3Query)
      ->selectRaw('wip.Package_Name, plref.production_line AS PL, SUM(wip.Qty) AS total_quantity, COUNT(DISTINCT wip.Lot_Id) AS total_lots')
      ->groupBy('wip.Package_Name', 'plref.production_line');

    $lfcspQuery = DB::table(self::F1F2_TABLE . ' as wip')
      ->selectRaw("
          'LFCSP' AS Package_Name,
          'PL1' AS PL,
          SUM(wip.Qty) AS total_quantity,
          COUNT(DISTINCT wip.Lot_Id) AS total_lots
      ")
      ->whereIn('wip.Part_Name', WipConstants::SPECIAL_PART_NAMES)
      ->where(fn($q) => $this->wipRepository->applyF1Filters($q, WipConstants::F1_EXCLUDED_PLANT, 'wip'));

    $lfcspQuery = $this->wipRepository->applyStationFilter($lfcspQuery, WipConstants::REEL_TRANSFER_B3, WipConstants::REEL_TRANSFER_EXCLUDED_STATIONS_F1)
      ->groupBy(DB::raw("'LFCSP'"))
      ->orderByDesc('total_quantity');

    $lfcspQuery = $this->applyDateOrWorkweekFilter($lfcspQuery, 'wip', $useWorkweek, $workweek, $startDate, $endDate);

    $results = DB::table(DB::raw("({$f1Query->toSql()}) as f1"))
      ->mergeBindings($f1Query)
      ->leftJoin(DB::raw("({$f2Query->toSql()}) as f2"), 'f1.Package_Name', '=', 'f2.Package_Name')
      ->mergeBindings($f2Query)
      ->leftJoin(DB::raw("({$f3Query->toSql()}) as f3"), 'f1.Package_Name', '=', 'f3.Package_Name')
      ->mergeBindings($f3Query)
      ->selectRaw('
          f1.Package_Name,
          COALESCE(f1.total_quantity, 0) AS f1_total_quantity,
          COALESCE(f2.total_quantity, 0) AS f2_total_quantity,
          COALESCE(f3.total_quantity, 0) AS f3_total_quantity,
          (COALESCE(f1.total_quantity, 0) + COALESCE(f2.total_quantity, 0) + COALESCE(f3.total_quantity, 0)) AS total_quantity,
          (COALESCE(f1.total_lots, 0) + COALESCE(f2.total_lots, 0) + COALESCE(f3.total_lots, 0)) AS total_lots
      ')
      ->orderByDesc('total_quantity')
      ->get();

    $totalQuantity = $results->sum('total_quantity');

    $allPackages = DB::table(DB::raw("({$f1PLQuery->toSql()} UNION ALL {$lfcspQuery->toSql()}) as f1"))
      ->mergeBindings($f1PLQuery)
      ->mergeBindings($lfcspQuery)
      ->leftJoin(DB::raw("({$f2PLQuery->toSql()}) as f2"), function ($join) {
        $join->on('f1.Package_Name', '=', 'f2.Package_Name')
          ->on('f1.PL', '=', 'f2.PL');
      })
      ->mergeBindings($f2PLQuery)
      ->leftJoin(DB::raw("({$f3PLQuery->toSql()}) as f3"), function ($join) {
        $join->on('f1.Package_Name', '=', 'f3.Package_Name')
          ->on('f1.PL', '=', 'f3.PL');
      })
      ->mergeBindings($f3PLQuery)
      ->selectRaw('
          f1.Package_Name,
          f1.PL,
          f1.total_quantity as f1_total_quantity,
          f1.total_lots as f1_total_lots,
          f2.total_quantity as f2_total_quantity,
          f2.total_lots as f2_total_lots,
          f3.total_quantity as f3_total_quantity,
          f3.total_lots as f3_total_lots,
          (COALESCE(f1.total_quantity, 0) + COALESCE(f2.total_quantity, 0) + COALESCE(f3.total_quantity, 0)) AS total_quantity
      ')
      ->orderByDesc('total_quantity')
      ->get();

    return response()->json([
      'status' => 'success',
      'message' => 'Highly optimized data retrieved successfully',
      'total_quantity' => $totalQuantity,
      'data' => $results,
      'all_packages' => $allPackages
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

  private function applyLineConditions($query, $filterType): Builder
  {
    if ($filterType === 'F1') {
      $query = $this->wipRepository->applyF1Filters($query, WipConstants::F1_EXCLUDED_PLANT, 'wip');
      $query = $this->wipRepository->applyStationFilter($query, WipConstants::REEL_TRANSFER_B3, WipConstants::REEL_TRANSFER_EXCLUDED_STATIONS_F1);
    }

    if ($filterType === 'F2') {
      $query->where(fn($q) => $this->wipRepository->applyF2Filters($q, WipConstants::EWAN_PROCESS, 'wip'));
    }

    if (in_array($filterType, ['PL1', 'PL6'])) {
      $query = $this->wipRepository->joinPL($query, WipConstants::SPECIAL_PART_NAMES, $filterType);

      $query->where(function ($sub) {
        $this->wipRepository->applyF1Filters($sub, WipConstants::F1_EXCLUDED_PLANT, 'wip');
        $this->wipRepository->applyStationFilter($sub, WipConstants::REEL_TRANSFER_B3, WipConstants::REEL_TRANSFER_EXCLUDED_STATIONS_F1);

        $sub->orWhere(function ($f2) {
          $f2->where(fn($q) => $this->wipRepository->applyF2Filters($q, WipConstants::EWAN_PROCESS, 'wip'));
        });
      });
    }

    return $query;
  }

  private function buildBaseQuery($filterType, $startOfDay, $endOfDay, $rules): Builder
  {
    $query = DB::table(self::F1F2_TABLE . ' as wip')
      ->whereBetween('wip.Date_Loaded', [$startOfDay, $endOfDay]);

    $query = $this->applyLineConditions($query, $filterType);
    $query = $this->applyFilterRules($query, $rules);

    return $query->selectRaw('SUM(wip.Qty) as total_qty, COUNT(DISTINCT wip.Lot_Id) as total_lots');
  }

  private function addAdditionalBakeCondition($query, $startOfDay, $endOfDay, $filterType = null)
  {
    $mainBakeQuery = DB::table(self::F1F2_TABLE . ' as wip')
      ->whereBetween('wip.Date_Loaded', [$startOfDay, $endOfDay])
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

    $mainBakeQuery->selectRaw('SUM(wip.Qty) AS total_qty, COUNT(DISTINCT wip.Lot_Id) AS total_lots');

    return $mainBakeQuery->unionAll($query);
  }

  public function getWIPFilterSummary($filterType, $startDate, $endDate, $filteringCondition)
  {
    $filterRules = $this->getFilterRules();

    $query = $this->buildBaseQuery($filterType, $startDate, $endDate, $filterRules[$filteringCondition] ?? null);

    if ($filteringCondition === 'Bake') {
      $query = $this->addAdditionalBakeCondition($query, $startDate, $endDate, $filterType);
    }

    // if (in_array($filteringCondition, ['All', 'Processable'])) {
    //   $f3Query = $this->wipRepository->baseF3Query(true)
    //     ->where('plref.production_line', $filterType)
    //     ->whereBetween('wip.Date_Loaded', [$startDate, $endDate])
    //     ->selectRaw('SUM(wip.Qty) as total_qty, COUNT(DISTINCT wip.Lot_Id) as total_lots');

    //   $combinedQuery = $query->unionAll($f3Query);
    // } else {
    // $combinedQuery = $query;
    // }

    // TODO: no need F3?? why?
    $combinedQuery = $query;

    return DB::query()
      ->fromSub($combinedQuery, 'combined_results')
      ->selectRaw('SUM(total_qty) as final_total_qty, SUM(total_lots) as final_total_lots')
      ->first();
  }

  private function getFilterRules()
  {
    return [
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
  }

  public function getOverallPickUp($startDate, $endDate)
  {
    // throw new Exception("test");
    $result = new \stdClass();

    $result->total_quantity = $this->pickUpRepository->getTotalQuantity($startDate, $endDate);

    foreach (['F1', 'F2', 'F3'] as $factory) {
      $key = strtolower($factory) . '_total_quantity';
      $result->{$key} = $this->pickUpRepository->getFactoryTotalQuantity($factory, $startDate, $endDate);
    }

    $pl_list = ['PL1', 'PL6'];
    foreach (['F1', 'F2', 'F3'] as $factory) {
      foreach ($pl_list as $pl) {
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
