<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class WipController extends Controller
{
  private const EXCLUDED_FOCUS_GROUPS = ['CV', 'CV1', 'LT', 'LTCL', 'LTI'];
  private const INCLUDED_FOCUS_GROUPS = ['CV', 'LTI', 'LTCL', 'LT'];
  private const EXCLUDED_STATIONS_F1 = [
    'GTTRES_T',
    'GTSUBCON',
    'GTARCH_T',
    'GTTBINLOC',
    'GTBRAND',
    'GTGOUT',
    'GTTBOX',
    'GTTFVI',
    'GTTOQA',
    'GTTRANS_B3'
  ];
  private const EXCLUDED_STATIONS_F1_PL = [
    'GTTRES_T',
    'GTSUBCON',
    'GTARCH_T',
    'GTTBINLOC',
    'GTRANS_BOX',
    'GTTRANS_QA',
    'GTBRAND',
    'GTGOUT',
    'GTTBOX',
    'GTTFVI',
    'GTTOQA',
    'GTTRANS_B3',
  ];
  private const EXCLUDED_STATIONS_F2 = [
    'GTTRES_T',
    'GTSUBCON',
    'GTARCH_T',
    'GTGOUT',
    'GTTBINLOC',
    'Q-PITRANS1'
  ];
  private const SPECIAL_PART_NAMES = [
    'ADXL312WACPZ',
    'ADXL312WACPZ-RL',
    'ADXL312ACPZ-RL',
    'ADXL313WACPZ-RL',
    'ADXL313WACPZ-RL7',
    'ADXL180WCPZA-RL',
    'ADXL314WBCPZ-RL',
  ];

  private const factory_1_station = "GTREEL";
  private const factory_1_excluded_plant = "ADPI";

  private function applyDateOrWorkweekFilter($query, $alias, $useWorkweek, $workweekArray, $startDate = null, $endDate = null)
  {
    if ($useWorkweek) {
      return $query->join('analog_calendar as cal', DB::raw("DATE(`$alias`.`Date_Loaded`)"), '=', 'cal.cal_date')
        ->whereIn('cal.cal_workweek', $workweekArray);
    }

    return $query->whereBetween("$alias.Date_Loaded", [$startDate, $endDate]);
  }


  public function getTodayWip()
  {
    try {
      // Generate date range (last 7 days including today)
      $endDate = now()->toDateString(); // YYYY-MM-DD
      $startDate = now()->subDays(20)->toDateString();

      // --------------------------------
      // Fetch F3 Data
      // --------------------------------
      $f3DataRaw = DB::table('f3_data_wip')
        ->selectRaw('DATE(Date_Loaded) AS report_date, SUM(Qty) AS f3_wip')
        ->whereBetween('Date_Loaded', ["$startDate 00:00:00", "$endDate 23:59:59"])
        ->groupBy(DB::raw('DATE(Date_Loaded)'))
        ->get();

      // Convert into keyed array for easy lookup
      $f3Data = [];
      foreach ($f3DataRaw as $row) {
        $f3Data[$row->report_date] = (int) $row->f3_wip;
      }

      // --------------------------------
      // Fetch F1 + F2 Data
      // --------------------------------
      $f1f2Data = DB::table('customer_data_wip')
        ->selectRaw("
                    DATE(Date_Loaded) AS report_date,
                    SUM(Qty) AS total_wip,
                    SUM(
                        CASE 
                            WHEN Focus_Group NOT IN ('CV', 'CV1', 'LT', 'LTCL', 'LTI', 'DLT', 'WLT', 'SOF')
                                AND Plant != 'ADPI'
                                AND (
                                    Station IN (
                                        'GTREEL', 'CVDTRAN_GT', 'GTARCH_T', 'GTTRANS_BE', 
                                        'GTTRES_T', 'PITBOX_T', 'PITBOX1', 'PITFVI1', 
                                        'PITLABEL1', 'PITOQA', 'PITOQA1', 'Q-PITRANS1'
                                    )
                                    OR (
                                        Station LIKE '%_T' 
                                        AND Station NOT IN (
                                            'GTSUBCON', 'GTTBINLOC', 
                                            'GTRANS_BOX', 'GTTRANS_QA', 'GTBRAND', 
                                            'GTGOUT', 'GTTBOX', 'GTTFVI', 'GTTOQA', 'GTTRANS_B3'
                                        )
                                    )
                                )
                            THEN Qty ELSE 0 
                        END
                    ) AS f1_wip,
                    SUM(
                        CASE 
                            WHEN Focus_Group IN ('CV', 'LTI', 'LTCL', 'LT')  
                                AND Station NOT IN ('GTTRES_T','GTGOUT', 'GTSUBCON', 'GTARCH_T', 'GTTBINLOC', 'Q-PITRANS1', 'GTTRANS_BE')  
                            THEN Qty ELSE 0 
                        END
                    ) AS f2_wip
                ")
        ->whereBetween('Date_Loaded', ["$startDate 00:00:00", "$endDate 23:59:59"])
        ->groupBy(DB::raw('DATE(Date_Loaded)'))
        ->get();

      // --------------------------------
      // Combine Results
      // --------------------------------
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
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'failed',
        'message' => $e->getMessage()
      ], 500);
    }
  }

  public function getOverallWip(Request $request)
  {
    $dateRange = $request->input('dateRange', ''); // e.g., '2024-11-03 - 2024-11-16'
    $workweek  = $request->input('workweek', ''); // e.g., '509 510'

    // $dateRange = '2024-11-03 - 2024-11-16';
    // $workweek = ""; // "501 502 503";
    // $workweek = "509 510";
    // $workweek = "501";

    $useWorkweek = !empty($workweek);
    $workweekArray = [];

    $startDate = date('Y-m-d') . ' 00:00:00';
    $endDate   = date('Y-m-d') . ' 23:59:59';

    if ($useWorkweek) {
      $workweekArray = array_map('intval', explode(' ', $workweek));
    } elseif (!empty($dateRange)) {
      [$startDate, $endDate] = explode(' - ', $dateRange);
      $startDate = date('Y-m-d', strtotime($startDate)) . ' 00:00:00';
      $endDate   = date('Y-m-d', strtotime($endDate)) . ' 23:59:59';
    } else {
      $startDate = date('Y-m-d') . ' 00:00:00';
      $endDate   = date('Y-m-d') . ' 23:59:59';
    }

    Log::info("Using " . ($useWorkweek ? "workweek" : "date range") . " filter");
    Log::info("Start Date: $startDate, End Date: $endDate");

    $f1f2Totals = DB::table('customer_data_wip as c')
      ->selectRaw("
            SUM(CASE 
                WHEN c.Focus_Group NOT IN ('" . implode("','", self::EXCLUDED_FOCUS_GROUPS) . "')
                     AND c.Plant != ('" . self::factory_1_excluded_plant . "') 
                     AND (c.Station IN ('" . self::factory_1_station . "') 
                     OR (c.Station LIKE '%_T' 
                         AND c.Station NOT IN ('" . implode("','", self::EXCLUDED_STATIONS_F1) . "')))
                THEN c.Qty ELSE 0 END) AS f1_total_quantity,

            SUM(CASE
                WHEN c.Focus_Group IN ('" . implode("','", self::INCLUDED_FOCUS_GROUPS) . "')
                     AND c.Station NOT IN ('" . implode("','", self::EXCLUDED_STATIONS_F2) . "')
                THEN c.Qty ELSE 0 END) AS f2_total_quantity
        ");
    $f1f2Totals = $this->applyDateOrWorkweekFilter($f1f2Totals, 'c', $useWorkweek, $workweekArray, $startDate, $endDate)
      ->first();

    $f1f2PlTotals = DB::table('customer_data_wip as c')
      ->join('ppc_productionline_packagereference as p', 'c.Package_Name', '=', 'p.Package')
      ->selectRaw("
            -- F1 PL6
            SUM(CASE 
                WHEN c.Focus_Group NOT IN ('" . implode("','", self::EXCLUDED_FOCUS_GROUPS) . "')
                    AND c.Plant != ('" . self::factory_1_excluded_plant . "')
                    AND (c.Station IN ('" . self::factory_1_station . "') 
                    OR (c.Station LIKE '%_T' 
                    AND c.Station NOT IN ('" . implode("','", self::EXCLUDED_STATIONS_F1_PL) . "')))
                    AND c.Part_Name NOT IN ('" . implode("','", self::SPECIAL_PART_NAMES) . "')
                    AND p.production_line = 'PL6'
                THEN c.Qty ELSE 0 END) AS f1pl6_total_quantity,

            -- F1 PL1
            SUM(CASE 
                WHEN c.Focus_Group NOT IN ('" . implode("','", self::EXCLUDED_FOCUS_GROUPS) . "')
                    AND c.Plant != ('" . self::factory_1_excluded_plant . "')
                    AND (c.Station IN ('" . self::factory_1_station . "') 
                    OR (c.Station LIKE '%_T' 
                    AND c.Station NOT IN ('" . implode("','", self::EXCLUDED_STATIONS_F1_PL) . "')))
                    AND (c.Part_Name IN ('" . implode("','", self::SPECIAL_PART_NAMES) . "')
                    OR p.production_line = 'PL1')
                THEN c.Qty ELSE 0 END) AS f1pl1_total_quantity,

            -- F2 PL6
            SUM(CASE
                WHEN c.Focus_Group IN ('" . implode("','", self::INCLUDED_FOCUS_GROUPS) . "')
                    AND c.Station NOT IN ('" . implode("','", self::EXCLUDED_STATIONS_F2) . "')
                    AND c.Part_Name NOT IN ('" . implode("','", self::SPECIAL_PART_NAMES) . "')
                    AND p.production_line = 'PL6'
                THEN c.Qty ELSE 0 END) AS f2pl6_total_quantity,

            -- F2 PL1
            SUM(CASE
                WHEN c.Focus_Group IN ('" . implode("','", self::INCLUDED_FOCUS_GROUPS) . "')
                    AND c.Station NOT IN ('" . implode("','", self::EXCLUDED_STATIONS_F2) . "')
                    AND (p.production_line = 'PL1' 
                    OR c.Part_Name IN ('" . implode("','", self::SPECIAL_PART_NAMES) . "'))
                THEN c.Qty ELSE 0 END) AS f2pl1_total_quantity
        ");

    $query = $this->applyDateOrWorkweekFilter($f1f2PlTotals, 'c', $useWorkweek, $workweekArray, $startDate, $endDate);

    $rowCount = (clone $query)->count();
    Log::info("Joined row count: " . $rowCount);

    $f1f2PlTotals = $query->first();

    $f3Total = DB::table('f3_data_wip as f3')
      ->where('f3.Focus_Group', 'F3');

    $f3Total = $this->applyDateOrWorkweekFilter($f3Total, 'f3', $useWorkweek, $workweekArray, $startDate, $endDate)
      ->selectRaw("SUM(f3.Qty) AS f3_total_quantity")
      ->first();

    $f3PlTotals = DB::table('f3_data_wip as f3')
      ->join('ppc_productionline_packagereference as ref', 'f3.Package_Name', '=', 'ref.Package')
      ->where('f3.Focus_Group', 'F3');

    $f3PlTotals = $this->applyDateOrWorkweekFilter($f3PlTotals, 'f3', $useWorkweek, $workweekArray, $startDate, $endDate)
      ->selectRaw("
            SUM(CASE WHEN ref.production_line = 'PL1' THEN f3.Qty ELSE 0 END) AS f3pl1_total_quantity,
            SUM(CASE WHEN ref.production_line = 'PL6' THEN f3.Qty ELSE 0 END) AS f3pl6_total_quantity
        ")
      ->first();

    $f1Total = (int) $f1f2Totals->f1_total_quantity;
    $f2Total = (int) $f1f2Totals->f2_total_quantity;
    $f3TotalQty = (int) $f3Total->f3_total_quantity;

    $grandTotal = $f1Total + $f2Total + $f3TotalQty;

    return response()->json([
      'f1_total_quantity' => $f1Total,
      'f2_total_quantity' => $f2Total,
      'f3_total_quantity' => $f3TotalQty,
      'total_f1_pl1' => (int) $f1f2PlTotals->f1pl1_total_quantity,
      'total_f1_pl6' => (int) $f1f2PlTotals->f1pl6_total_quantity,
      'total_f2_pl1' => (int) $f1f2PlTotals->f2pl1_total_quantity,
      'total_f2_pl6' => (int) $f1f2PlTotals->f2pl6_total_quantity,
      'total_f3_pl1' => (int) $f3PlTotals->f3pl1_total_quantity,
      'total_f3_pl6' => (int) $f3PlTotals->f3pl6_total_quantity,
      'total_quantity' => $grandTotal,
      'status' => 'success',
      'message' => 'Data retrieved successfully'
    ]);
  }

  /**
   * Parse and validate a date range string.
   *
   * @param string $dateRange Format: "m/d/Y H:i:s - m/d/Y H:i:s"
   * @return array ['start' => 'Y-m-d H:i:s', 'end' => 'Y-m-d H:i:s']
   * @throws \Exception if invalid
   */
  private function parseDateRange(string $dateRange = ''): array
  {
    if ($dateRange) {
      $dateParts = explode(' - ', $dateRange);

      if (count($dateParts) !== 2) {
        throw new \Exception('Date range must contain two parts.');
      }

      $start = \DateTime::createFromFormat('m/d/Y H:i:s', trim($dateParts[0]));
      $end   = \DateTime::createFromFormat('m/d/Y H:i:s', trim($dateParts[1]));

      if (!$start || !$end) {
        throw new \Exception('Date parsing failed.');
      }

      if ($start > $end) {
        throw new \Exception('End date must be greater than or equal to start date.');
      }

      return [
        'start' => $start->format('Y-m-d H:i:s'),
        'end'   => $end->format('Y-m-d H:i:s'),
      ];
    }

    // Default to today
    $today = now()->format('Y-m-d');
    return [
      'start' => $today . ' 00:00:00',
      'end'   => $today . ' 23:59:59',
    ];
  }

  public function getOverallPickUp(Request $request)
  {
    try {
      $range = $this->parseDateRange($request->input('dateRange', ''));
      $start_datetime = $range['start'];
      $end_datetime   = $range['end'];
    } catch (\Exception $e) {
      return response()->json([
        'status'  => 'error',
        'message' => 'Invalid date format: ' . $e->getMessage(),
      ], 400);
    }

    $result = DB::table('ppc_pickupdb')
      ->join(DB::raw('(SELECT DISTINCT Partname, Factory, PL FROM ppc_partnamedb) as partname'), 'ppc_pickupdb.PARTNAME', '=', 'partname.Partname')
      ->whereBetween('ppc_pickupdb.DATE_CREATED', [$start_datetime, $end_datetime])
      ->selectRaw("
        SUM(ppc_pickupdb.QTY) AS total_qty,
        SUM(CASE WHEN partname.Factory = 'F1' THEN ppc_pickupdb.QTY ELSE 0 END) AS f1_total_qty,
        SUM(CASE WHEN partname.Factory = 'F2' THEN ppc_pickupdb.QTY ELSE 0 END) AS f2_total_qty,
        SUM(CASE WHEN partname.Factory = 'F3' THEN ppc_pickupdb.QTY ELSE 0 END) AS f3_total_qty,
        SUM(CASE WHEN partname.Factory = 'F1' AND partname.PL = 'PL1' THEN ppc_pickupdb.QTY ELSE 0 END) AS f1pl1_total_qty,
        SUM(CASE WHEN partname.Factory = 'F1' AND partname.PL = 'PL6' THEN ppc_pickupdb.QTY ELSE 0 END) AS f1pl6_total_qty,
        SUM(CASE WHEN partname.Factory = 'F2' AND partname.PL = 'PL1' THEN ppc_pickupdb.QTY ELSE 0 END) AS f2pl1_total_qty,
        SUM(CASE WHEN partname.Factory = 'F2' AND partname.PL = 'PL6' THEN ppc_pickupdb.QTY ELSE 0 END) AS f2pl6_total_qty,
        SUM(CASE WHEN partname.Factory = 'F3' AND partname.PL = 'PL1' THEN ppc_pickupdb.QTY ELSE 0 END) AS f3pl1_total_qty,
        SUM(CASE WHEN partname.Factory = 'F3' AND partname.PL = 'PL6' THEN ppc_pickupdb.QTY ELSE 0 END) AS f3pl6_total_qty
    ")
      ->first();

    $pl1_total_qty = (int) $result->f1pl1_total_qty + (int) $result->f2pl1_total_qty + (int) $result->f3pl1_total_qty;
    $pl6_total_qty = (int) $result->f1pl6_total_qty + (int) $result->f2pl6_total_qty + (int) $result->f3pl6_total_qty;

    return response()->json([
      'total_quantity' => (int) $result->total_qty,
      'f1_total_quantity' => (int) $result->f1_total_qty,
      'f2_total_quantity' => (int) $result->f2_total_qty,
      'f3_total_quantity' => (int) $result->f3_total_qty,
      'total_f1_pl1' => (int) $result->f1pl1_total_qty,
      'total_f1_pl6' => (int) $result->f1pl6_total_qty,
      'total_f2_pl1' => (int) $result->f2pl1_total_qty,
      'total_f2_pl6' => (int) $result->f2pl6_total_qty,
      'total_f3_pl1' => (int) $result->f3pl1_total_qty,
      'total_f3_pl6' => (int) $result->f3pl6_total_qty,
      'total_pl1' => $pl1_total_qty,
      'total_pl6' => $pl6_total_qty,
      'status' => 'success',
      'message' => 'Data retrieved successfully'
    ]);
  }


  public function getPackagePickUpSummary(Request $request)
  {
    try {
      $range = $this->parseDateRange($request->input('dateRange', ''));
      $start_datetime = $range['start'];
      $end_datetime   = $range['end'];
    } catch (\Exception $e) {
      return response()->json([
        'status'  => 'error',
        'message' => 'Invalid date format: ' . $e->getMessage(),
      ], 400);
    }

    // 2. Validate chartStatus
    $allowedChartStatuses = ['all', 'F1', 'F2', 'F3', 'PL1', 'PL6'];
    $chartStatus = $request->input('chartStatus', 'all');

    if (!in_array($chartStatus, $allowedChartStatuses)) {
      return response()->json([
        'status'  => 'error',
        'message' => 'Invalid chart status: ' . $chartStatus,
      ], 400);
    }

    switch ($chartStatus) {
      case 'all':
      case 'F1':
        $query = DB::table('customer_data_wip as wip')
          ->selectRaw('wip.Package_Name AS package_name, SUM(Qty) AS total_quantity, COUNT(DISTINCT Lot_Id) AS total_lots')
          ->where('wip.Station', 'GTTRES_T')
          ->whereBetween('wip.Date_Loaded', [$start_datetime, $end_datetime]);

        if ($chartStatus === 'all') {
          $query->where(function ($q) {
            $q->where(function ($sub) {
              $sub->whereNotIn('wip.Focus_Group', ['CV', 'CV1', 'LT', 'LTCL', 'LTI'])
                ->where('wip.Plant', '!=', 'ADPI')
                ->where(function ($s) {
                  $s->whereIn('wip.Station', ['GTREEL', 'GTTRANS_B3'])
                    ->orWhere(function ($t) {
                      $t->where('wip.Station', 'like', '%_T')
                        ->whereNotIn('wip.Station', [
                          'GTSUBCON',
                          'GTARCH_T',
                          'GTTBINLOC',
                          'GTRANS_BOX',
                          'GTTRANS_QA',
                          'GTBRAND',
                          'GTGOUT',
                          'GTTBOX',
                          'GTTFVI',
                          'GTTOQA'
                        ]);
                    });
                });
            })
              ->orWhere(function ($sub) {
                $sub->whereIn('wip.Focus_Group', ['CV', 'LTI', 'LTCL', 'LT'])
                  ->whereNotIn('wip.Station', ['GTSUBCON', 'GTARCH_T', 'GTGOUT', 'GTTBINLOC']);
              });
          });
        }

        $query->groupBy('package_name')
          ->orderByDesc('total_quantity');
        break;

      case 'F2':
      case 'F3':
      case 'PL1':
      case 'PL6':
        $query = DB::table('ppc_pickupdb')
          ->selectRaw('ppc_pickupdb.PACKAGE AS package_name, SUM(QTY) AS total_quantity, COUNT(DISTINCT LOTID) AS total_lots')
          ->join('ppc_partnamedb as partname', 'ppc_pickupdb.PARTNAME', '=', 'partname.Partname')
          ->whereBetween('ppc_pickupdb.DATE_CREATED', [$start_datetime, $end_datetime]);

        // Add filters dynamically
        if ($chartStatus === 'F2' || $chartStatus === 'F3') {
          $query->where('partname.Factory', strtoupper($chartStatus));
        } elseif ($chartStatus === 'PL1' || $chartStatus === 'PL6') {
          $query->where('partname.PL', $chartStatus);
        }

        $query->groupBy('package_name')
          ->orderByDesc('total_quantity');
        break;

      default:
        $query = DB::table('ppc_pickupdb')
          ->selectRaw('PACKAGE AS package_name, SUM(QTY) AS total_quantity, COUNT(DISTINCT LOTID) AS total_lots')
          ->whereDate('DATE_CREATED', now())
          ->groupBy('package_name')
          ->orderByDesc('total_quantity');
        break;
    }

    // Execute
    $results = $query->get();

    return response()->json([
      'data' => $results,
      'status' => 'success',
      'message' => 'Data retrieved successfully'
    ]);
  }

  public function getWIPQuantityAndLotsTotal()
  {
    try {
      $todayStart = new \DateTime('2025-09-01 00:00:00');
      $tomorrowStart = new \DateTime('2025-09-01 23:59:59');

      // Single query for F1 + F2
      $f1f2 = DB::table('customer_data_wip as wip')
        ->select(
          'wip.Package_Name',
          DB::raw("
          SUM(CASE
            WHEN wip.Focus_Group NOT IN ('CV', 'CV1', 'LT', 'LTCL', 'LTI')
              AND wip.Plant != 'ADPI'
              AND (
                wip.Station IN ('GTREEL', 'GTTRANS_B3')
                OR (wip.Station LIKE '%_T'
                    AND wip.Station NOT IN (
                      'GTTRES_T','GTSUBCON','GTARCH_T','GTTBINLOC',
                      'GTRANS_BOX','GTTRANS_QA','GTBRAND','GTGOUT',
                      'GTTBOX','GTTFVI','GTTOQA'
                    ))
              )
            THEN wip.Qty ELSE 0 END
          ) AS f1_total_quantity
        "),
          DB::raw("
          SUM(CASE
            WHEN wip.Focus_Group IN ('CV','LTI','LTCL','LT')
              AND wip.Station NOT IN (
                'GTTRES_T','GTSUBCON','GTGOUT','GTARCH_T','GTTBINLOC'
              )
            THEN wip.Qty ELSE 0 END
          ) AS f2_total_quantity
        "),
          DB::raw('COUNT(DISTINCT wip.Lot_Id) AS total_lots')
        )
        ->whereBetween('wip.Date_Loaded', [$todayStart, $tomorrowStart])
        ->groupBy('wip.Package_Name');

      // Separate F3 query
      $f3 = DB::table('f3_data_wip as f3')
        ->select(
          'f3.Package_Name',
          DB::raw('SUM(f3.Qty) AS f3_total_quantity'),
          DB::raw('COUNT(DISTINCT f3.Lot_Id) AS total_lots')
        )
        ->where('f3.Focus_Group', '=', 'F3')
        ->whereBetween('f3.Date_Loaded', [$todayStart, $tomorrowStart])
        ->groupBy('f3.Package_Name');

      // Merge F3 into F1/F2 results via LEFT JOIN (efficient)
      $results = DB::table(DB::raw("({$f1f2->toSql()}) as f1f2"))
        ->mergeBindings($f1f2)
        ->leftJoin(DB::raw("({$f3->toSql()}) as f3"), 'f1f2.Package_Name', '=', 'f3.Package_Name')
        ->mergeBindings($f3)
        ->select(
          'f1f2.Package_Name',
          'f1f2.f1_total_quantity',
          'f1f2.f2_total_quantity',
          DB::raw('COALESCE(f3.f3_total_quantity, 0) AS f3_total_quantity'),
          DB::raw('(f1f2.f1_total_quantity + f1f2.f2_total_quantity + COALESCE(f3.f3_total_quantity, 0)) AS grand_total_quantity'),
          'f1f2.total_lots'
        )
        ->orderByDesc('grand_total_quantity')
        ->get();

      $totalQuantity = $results->sum('grand_total_quantity');

      return response()->json([
        'status' => 'success',
        'message' => 'Highly optimized data retrieved successfully',
        'total_quantity' => $totalQuantity,
        'data' => $results
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'failed',
        'message' => $e->getMessage(),
      ]);
    }
  }

  public function getWIPQuantityAndLotsTotalPL()
  {
    try {
      // Define time range (no need to create variables separately)
      $todayStart = '2025-09-01 00:00:00';
      $todayEnd = '2025-09-01 23:59:59';

      // Base F1 + F2 query
      $f1f2 = DB::table('customer_data_wip as wip')
        ->join('ppc_productionline_packagereference as plref', 'plref.Package', '=', 'wip.Package_Name')
        ->select(
          'wip.Package_Name',
          DB::raw('SUM(wip.Qty) AS total_quantity'),
          DB::raw('COUNT(DISTINCT wip.Lot_Id) AS total_lots'),
          DB::raw("'F1/F2' AS Filter_Type"),
          // DB::raw("'F1/F2' AS Filter_Type"),
        )
        ->whereBetween('wip.Date_Loaded', [$todayStart, $todayEnd])
        ->where('plref.production_line', 'PL1')
        ->where(function ($query) {
          $query->where(function ($q1) {
            $q1->whereNotIn('wip.Focus_Group', ['CV', 'CV1', 'LT', 'LTCL', 'LTI'])
              ->where('wip.Plant', '!=', 'ADPI')
              ->where(function ($station) {
                $station->whereIn('wip.Station', ['GTREEL', 'GTTRANS_B3'])
                  ->orWhere(function ($sub) {
                    $sub->where('wip.Station', 'like', '%_T')
                      ->whereNotIn('wip.Station', [
                        'GTTRES_T',
                        'GTSUBCON',
                        'GTARCH_T',
                        'GTTBINLOC',
                        'GTRANS_BOX',
                        'GTTRANS_QA',
                        'GTBRAND',
                        'GTGOUT',
                        'GTTBOX',
                        'GTTFVI',
                        'GTTOQA'
                      ]);
                  });
              });
          })
            ->orWhere(function ($q2) {
              $q2->whereIn('wip.Focus_Group', ['CV', 'LTI', 'LTCL', 'LT'])
                ->whereNotIn('wip.Station', [
                  'GTTRES_T',
                  'GTGOUT',
                  'GTSUBCON',
                  'GTARCH_T',
                  'GTTBINLOC'
                ]);
            });
        })
        ->groupBy('wip.Package_Name');

      // F3 query
      $f3 = DB::table('f3_data_wip as f3')
        ->join('ppc_productionline_packagereference as plref', 'plref.Package', '=', 'f3.Package_Name')
        ->select(
          'f3.Package_Name',
          DB::raw('SUM(f3.Qty) AS total_quantity'),
          DB::raw('COUNT(DISTINCT f3.Lot_Id) AS total_lots'),
          DB::raw("'F3' AS Filter_Type")
        )
        ->whereBetween('f3.Date_Loaded', [$todayStart, $todayEnd])
        ->where('plref.production_line', 'PL1')
        ->groupBy('f3.Package_Name');

      // LFCSP query
      $lfcsp = DB::table('customer_data_wip as wip')
        ->select(
          DB::raw("'LFCSP' AS Package_Name"),
          DB::raw('SUM(wip.Qty) AS total_quantity'),
          DB::raw('COUNT(DISTINCT wip.Lot_Id) AS total_lots'),
          DB::raw("'F1/F2' AS Filter_Type")
        )
        ->whereIn('wip.Part_Name', [
          'ADXL312WACPZ',
          'ADXL312WACPZ-RL',
          'ADXL312ACPZ-RL',
          'ADXL313WACPZ-RL',
          'ADXL313WACPZ-RL7',
          'ADXL180WCPZA-RL',
          'ADXL314WBCPZ-RL'
        ])
        ->whereBetween('wip.Date_Loaded', [$todayStart, $todayEnd])
        ->where(function ($query) {
          $query->whereNotIn('wip.Focus_Group', ['CV', 'CV1', 'LT', 'LTCL', 'LTI'])
            ->where('wip.Plant', '!=', 'ADPI')
            ->where(function ($station) {
              $station->whereIn('wip.Station', ['GTREEL', 'GTTRANS_B3'])
                ->orWhere(function ($sub) {
                  $sub->where('wip.Station', 'like', '%_T')
                    ->whereNotIn('wip.Station', [
                      'GTTRES_T',
                      'GTSUBCON',
                      'GTARCH_T',
                      'GTTBINLOC',
                      'GTRANS_BOX',
                      'GTTRANS_QA',
                      'GTBRAND',
                      'GTGOUT',
                      'GTTBOX',
                      'GTTFVI',
                      'GTTOQA'
                    ]);
                });
            });
        })
        ->groupBy(DB::raw("'LFCSP'"));

      // Combine all three efficiently
      $results = $f1f2->unionAll($f3)->unionAll($lfcsp);

      $data = DB::query()->fromSub($results, 'combined')
        ->orderByDesc('total_quantity')
        ->get();

      return response()->json([
        'status' => 'success',
        'message' => 'Highly optimized unified data retrieved successfully',
        'data' => $data
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'failed',
        'message' => $e->getMessage(),
      ]);
    }
  }


  public function wipTable()
  {
    return Inertia::render('WIPTable');
  }
}
