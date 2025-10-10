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


    $f1Query = DB::table('customer_data_wip_sample as wip')
      ->select(
        DB::raw('SUM(wip.Qty) AS f1_total_quantity'),
      )
      ->where('wip.f1_focus_group_flag', 1)
      ->where('wip.Plant', '!=', 'ADPI')
      ->where(function ($query) {
        $query->whereIn('wip.Station', ['GTREEL'])
          ->orWhere(function ($q) {
            $q->where('wip.station_suffix', '=', '_T')
              ->whereNotIn('wip.Station', self::EXCLUDED_STATIONS_F1);
          });
      });

    $f1Query = $this->applyDateOrWorkweekFilter($f1Query, 'wip', $useWorkweek, $workweekArray, $startDate, $endDate);
    $f1QueryResult = $f1Query->first();

    $f2Query = DB::table('customer_data_wip_sample as wip')
      ->select(
        DB::raw('SUM(wip.Qty) AS f2_total_quantity'),
      )
      ->where('wip.f2_focus_group_flag', 1)
      ->whereNotIn('wip.Station', self::EXCLUDED_STATIONS_F2);

    $f2Query = $this->applyDateOrWorkweekFilter($f2Query, 'wip', $useWorkweek, $workweekArray, $startDate, $endDate);
    $f2QueryResult = $f2Query->first();


    $f1pl6Query = DB::table('customer_data_wip_sample as wip')
      ->select(DB::raw('SUM(wip.Qty) AS f1pl6_total_quantity'))
      ->join('ppc_productionline_packagereference as p', 'wip.Package_Name', '=', 'p.Package')
      ->where('wip.f1_focus_group_flag', 1)
      ->where('wip.Plant', '!=', 'ADPI')
      ->where(function ($query) {
        $query->whereIn('wip.Station', ['GTREEL'])
          ->orWhere(function ($q) {
            $q->where('wip.station_suffix', '=', '_T')
              ->whereNotIn('wip.Station', self::EXCLUDED_STATIONS_F1_PL);
          });
      })
      ->whereNotIn('wip.Part_Name', self::SPECIAL_PART_NAMES)
      ->where('p.production_line', 'PL6');

    $f1pl6Query = $this->applyDateOrWorkweekFilter($f1pl6Query, 'wip', $useWorkweek, $workweekArray, $startDate, $endDate);
    $f1pl6QueryResult = $f1pl6Query->first();

    $f1pl1Query = DB::table('customer_data_wip_sample as wip')
      ->select(DB::raw('SUM(wip.Qty) AS f1pl1_total_quantity'))
      ->join('ppc_productionline_packagereference as p', 'wip.Package_Name', '=', 'p.Package')
      ->where('wip.f1_focus_group_flag', 1)
      ->where('wip.Plant', '!=', 'ADPI')
      ->where(function ($query) {
        $query->whereIn('wip.Station', ['GTREEL'])
          ->orWhere(function ($q) {
            $q->where('wip.station_suffix', '=', '_T')
              ->whereNotIn('wip.Station', self::EXCLUDED_STATIONS_F1_PL);
          });
      })
      ->where(function ($query) {
        $query->whereIn('wip.Part_Name', self::SPECIAL_PART_NAMES)
          ->orWhere('p.production_line', 'PL1');
      });

    $f1pl1Query = $this->applyDateOrWorkweekFilter($f1pl1Query, 'wip', $useWorkweek, $workweekArray, $startDate, $endDate);
    $f1pl1QueryResult = $f1pl1Query->first();

    $f2pl6Query = DB::table('customer_data_wip_sample as wip')
      ->select(DB::raw('SUM(wip.Qty) AS f2pl6_total_quantity'))
      ->join('ppc_productionline_packagereference as p', 'wip.Package_Name', '=', 'p.Package')
      ->where('wip.f2_focus_group_flag', 1)
      ->whereNotIn('wip.Station', self::EXCLUDED_STATIONS_F2)
      ->whereNotIn('wip.Part_Name', self::SPECIAL_PART_NAMES)
      ->Where('p.production_line', 'PL6');

    $f2pl6Query = $this->applyDateOrWorkweekFilter($f2pl6Query, 'wip', $useWorkweek, $workweekArray, $startDate, $endDate);
    $f2pl6QueryResult = $f2pl6Query->first();

    $f2pl1Query = DB::table('customer_data_wip_sample as wip')
      ->select(DB::raw('SUM(wip.Qty) AS f2pl1_total_quantity'))
      ->join('ppc_productionline_packagereference as p', 'wip.Package_Name', '=', 'p.Package')
      ->where('wip.f2_focus_group_flag', 1)
      ->whereNotIn('wip.Station', self::EXCLUDED_STATIONS_F2)
      ->where(function ($query) {
        $query->whereIn('wip.Part_Name', self::SPECIAL_PART_NAMES)
          ->orWhere('p.production_line', 'PL1');
      });

    $f2pl1Query = $this->applyDateOrWorkweekFilter($f2pl1Query, 'wip', $useWorkweek, $workweekArray, $startDate, $endDate);
    $f2pl1QueryResult = $f2pl1Query->first();

    // $f1f2PlTotals = DB::table('customer_data_wip_sample as c')
    //   ->join('ppc_productionline_packagereference as p', 'c.Package_Name', '=', 'p.Package')
    //   ->selectRaw("
    //         -- F1 PL6
    //         SUM(CASE 
    //             WHEN c.Focus_Group NOT IN ('" . implode("','", self::EXCLUDED_FOCUS_GROUPS) . "')
    //                 AND c.Plant != ('" . self::factory_1_excluded_plant . "')
    //                 AND (c.Station IN ('" . self::factory_1_station . "') 
    //                 OR (c.station_suffix = '_T' 
    //                 AND c.Station NOT IN ('" . implode("','", self::EXCLUDED_STATIONS_F1_PL) . "')))
    //                 AND c.Part_Name NOT IN ('" . implode("','", self::SPECIAL_PART_NAMES) . "')
    //                 AND p.production_line = 'PL6'
    //             THEN c.Qty ELSE 0 END) AS f1pl6_total_quantity,

    //         -- F1 PL1
    //         SUM(CASE 
    //             WHEN c.Focus_Group NOT IN ('" . implode("','", self::EXCLUDED_FOCUS_GROUPS) . "')
    //                 AND c.Plant != ('" . self::factory_1_excluded_plant . "')
    //                 AND (c.Station IN ('" . self::factory_1_station . "') 
    //                 OR (c.station_suffix = '_T' 
    //                 AND c.Station NOT IN ('" . implode("','", self::EXCLUDED_STATIONS_F1_PL) . "')))
    //                 AND (c.Part_Name IN ('" . implode("','", self::SPECIAL_PART_NAMES) . "')
    //                 OR p.production_line = 'PL1')
    //             THEN c.Qty ELSE 0 END) AS f1pl1_total_quantity,

    //         -- F2 PL6
    //         SUM(CASE
    //             WHEN c.Focus_Group IN ('" . implode("','", self::INCLUDED_FOCUS_GROUPS) . "')
    //                 AND c.Station NOT IN ('" . implode("','", self::EXCLUDED_STATIONS_F2) . "')
    //                 AND c.Part_Name NOT IN ('" . implode("','", self::SPECIAL_PART_NAMES) . "')
    //                 AND p.production_line = 'PL6'
    //             THEN c.Qty ELSE 0 END) AS f2pl6_total_quantity,

    //         -- F2 PL1
    //         SUM(CASE
    //             WHEN c.Focus_Group IN ('" . implode("','", self::INCLUDED_FOCUS_GROUPS) . "')
    //                 AND c.Station NOT IN ('" . implode("','", self::EXCLUDED_STATIONS_F2) . "')
    //                 AND (p.production_line = 'PL1' 
    //                 OR c.Part_Name IN ('" . implode("','", self::SPECIAL_PART_NAMES) . "'))
    //             THEN c.Qty ELSE 0 END) AS f2pl1_total_quantity
    //     ");

    // $query = $this->applyDateOrWorkweekFilter($f1f2PlTotals, 'c', $useWorkweek, $workweekArray, $startDate, $endDate);

    // $rowCount = (clone $query)->count();
    // Log::info("Joined row count: " . $rowCount);

    // $f1f2PlTotals = $query->first();

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

    $f3TotalQty = (int) $f3Total->f3_total_quantity;



    $f1Total = (int) ($f1QueryResult->f1_total_quantity ?? 0);
    $f2Total = (int) ($f2QueryResult->f2_total_quantity ?? 0);

    $f1TotalCombined = 0;
    $f2TotalCombined = 0;



    Log::info('--- F1/F2 Comparison ---', [
      'f1_total_separated' => $f1Total,
      'f2_total_separated' => $f2Total,
      'f1_total_combined' => $f1TotalCombined,
      'f2_total_combined' => $f2TotalCombined,
    ]);

    $grandTotal = $f1Total + $f2Total + $f3TotalQty;

    return response()->json([
      'f1_total_quantity' => $f1Total,
      'f2_total_quantity' => $f2Total,
      'f3_total_quantity' => $f3TotalQty,
      'total_f1_pl1' => (int) $f1pl1QueryResult->f1pl1_total_quantity,
      'total_f1_pl6' => (int) $f1pl6QueryResult->f1pl6_total_quantity,
      'total_f2_pl1' => (int) $f2pl1QueryResult->f2pl1_total_quantity,
      'total_f2_pl6' => (int) $f1pl6QueryResult->f1pl6_total_quantity,
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
      // ->join(DB::raw('(SELECT DISTINCT Partname, Factory, PL FROM ppc_partnamedb) as partname'), 'ppc_pickupdb.PARTNAME', '=', 'partname.Partname')
      ->join('ppc_partnamedb as partname', 'ppc_pickupdb.PARTNAME', '=', 'partname.Partname')
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
                          // TODO: 'GTTRES_T', not reallt included?
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
                  // TODO: 'GTTRES_T', not reallt included?
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

  public function getWIPQuantityAndLotsTotal(Request $request)
  {
    try {
      // $todayStart = new \DateTime('2025-09-01 00:00:00');
      // $tomorrowStart = new \DateTime('2025-09-01 23:59:59');

      // $dateRange = "2025/09/01 - 2025/09/01";
      // $dateRange = $this->parseDateRange($request->input('dateRange', ''));
      // $dateRange = $this->parseDateRange("09/01/2025 00:00:00 - 09/01/2025 23:59:59");
      $dateRange = $request->input('dateRange', '2025-09-01 - 2025-09-01'); // e.g., '2024-11-03 - 2024-11-16'
      // $workweek  = $request->input('workweek', '501'); // e.g., '509 510'
      $workweek  = $request->input('workweek', ''); // e.g., '509 510'

      // $useWorkweek = !empty($workweek);
      $useWorkweek = false;
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

      $f1Query = DB::table('customer_data_wip_sample as wip')
        ->select(
          'wip.Package_Name',
          DB::raw('SUM(wip.Qty) AS f1_total_quantity'),
          DB::raw('COUNT(DISTINCT wip.Lot_Id) AS total_lots')
        )
        // ->whereBetween('wip.Date_Loaded', [$startDate, $endDate])
        ->where('wip.f1_focus_group_flag', 1)
        ->where('wip.Plant', '!=', 'ADPI')
        ->where(function ($query) {
          $query->whereIn('wip.Station', ['GTREEL', 'GTTRANS_B3'])
            ->orWhere(function ($q) {
              $q->where('wip.station_suffix', '=', '_T')
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
        })
        ->groupBy('wip.Package_Name');

      $f1Query = $this->applyDateOrWorkweekFilter($f1Query, 'wip', $useWorkweek, $workweekArray, $startDate, $endDate);

      $f2Query = DB::table('customer_data_wip_sample as wip')
        ->select(
          'wip.Package_Name',
          DB::raw('SUM(wip.Qty) AS f2_total_quantity'),
          DB::raw('COUNT(DISTINCT wip.Lot_Id) AS total_lots')
        )
        // ->whereBetween('wip.Date_Loaded', [$startDate, $endDate])
        ->where('wip.f2_focus_group_flag', 1)
        ->whereNotIn('wip.Station', [
          'GTTRES_T',
          'GTSUBCON',
          'GTGOUT',
          'GTARCH_T',
          'GTTBINLOC'
        ])
        ->groupBy('wip.Package_Name');

      $f2Query = $this->applyDateOrWorkweekFilter($f2Query, 'wip', $useWorkweek, $workweekArray, $startDate, $endDate);

      $f3 = DB::table('f3_data_wip as f3')
        ->select(
          'f3.Package_Name',
          DB::raw('SUM(f3.Qty) AS f3_total_quantity'),
          DB::raw('COUNT(DISTINCT f3.Lot_Id) AS total_lots')
        )
        ->where('f3.Focus_Group', '=', 'F3')
        ->groupBy('f3.Package_Name');

      $f3 = $this->applyDateOrWorkweekFilter($f3, 'f3', $useWorkweek, $workweekArray, $startDate, $endDate);

      $total_lots = DB::table('customer_data_wip_sample as wip')
        ->select(
          'wip.Package_Name',
          DB::raw('COUNT(DISTINCT wip.Lot_Id) AS total_lots')
        )
        ->whereBetween('wip.Date_Loaded', [$startDate, $endDate])
        ->groupBy('wip.Package_Name');

      $results = DB::table(DB::raw("({$f1Query->toSql()}) as f1"))
        ->mergeBindings($f1Query)
        ->leftJoin(DB::raw("({$f2Query->toSql()}) as f2"), 'f1.Package_Name', '=', 'f2.Package_Name')
        ->mergeBindings($f2Query)
        ->leftJoin(DB::raw("({$f3->toSql()}) as f3"), 'f1.Package_Name', '=', 'f3.Package_Name')
        ->mergeBindings($f3)
        ->leftJoin(DB::raw("({$total_lots->toSql()}) as tl"), 'f1.Package_Name', '=', 'tl.Package_Name')
        ->mergeBindings($total_lots)
        ->select(
          'f1.Package_Name',
          DB::raw('COALESCE(f1.f1_total_quantity,0) AS f1_total_quantity'),
          DB::raw('COALESCE(f2.f2_total_quantity,0) AS f2_total_quantity'),
          DB::raw('COALESCE(f3.f3_total_quantity,0) AS f3_total_quantity'),
          DB::raw('(COALESCE(f1.f1_total_quantity,0) + COALESCE(f2.f2_total_quantity,0) + COALESCE(f3.f3_total_quantity,0)) AS total_quantity'),
          DB::raw('COALESCE(tl.total_lots,0) AS total_lots')
        )
        ->orderByDesc('total_quantity')
        ->get();

      $totalQuantity = $results->sum('total_quantity');

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

  public function getWIPQuantityAndLotsTotalPL(Request $request)
  {
    $specialPartNames = [
      'ADXL312WACPZ',
      'ADXL312WACPZ-RL',
      'ADXL312ACPZ-RL',
      'ADXL313WACPZ-RL',
      'ADXL313WACPZ-RL7',
      'ADXL180WCPZA-RL',
      'ADXL314WBCPZ-RL'
    ];

    // $dateRange = "2025/09/01 - 2025/09/01";
    // $dateRange = $this->parseDateRange($request->input('dateRange', ''));
    // $dateRange = $this->parseDateRange("09/01/2025 00:00:00 - 09/01/2025 23:59:59");
    $dateRange = $request->input('dateRange', '2025-09-01 - 2025-09-01'); // e.g., '2024-11-03 - 2024-11-16'
    // $workweek  = $request->input('workweek', '501'); // e.g., '509 510'
    $workweek  = $request->input('workweek', ''); // e.g., '509 510'

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

    // // Handle date range
    // if ($dateRange) {
    //   // list($startDate, $endDate) = explode(' - ', $dateRange);
    //   // $startDate = date('Y-m-d', strtotime($startDate));
    //   // $endDate = date('Y-m-d', strtotime($endDate));
    //   $startDate   = $dateRange['start'];
    //   $endDate = $dateRange['end'];
    // } else {
    //   $startDate = $endDate = date('Y-m-d');
    // }


    // F1 query (PL1 + PL6)
    $f1Query = DB::table('customer_data_wip_sample as wip')
      ->select(
        'wip.Package_Name',
        'plref.production_line as PL',
        DB::raw('SUM(wip.Qty) as f1_total_quantity'),
        DB::raw('COUNT(DISTINCT wip.Lot_Id) as f1_total_lots')
      )
      ->join('ppc_productionline_packagereference as plref', 'wip.Package_Name', '=', 'plref.Package')
      ->whereNotIn('wip.Part_Name', $specialPartNames)
      // ->whereBetween('wip.Date_Loaded', ["$startDate 00:00:00", "$endDate 23:59:59"])
      ->where(function ($q) {
        $q
          ->where('wip.f1_focus_group_flag', 1)
          // ->whereNotIn('wip.Focus_Group', ['CV', 'CV1', 'LT', 'LTCL', 'LTI'])
          ->where('wip.Plant', '!=', 'ADPI')
          ->where(function ($sub) {
            $sub->whereIn('wip.Station', ['GTREEL', 'GTTRANS_B3'])
              ->orWhere(function ($s) {
                $s
                  ->where('wip.station_suffix', '=', '_T')
                  // ->where('wip.Station', 'like', '%_T')
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
      ->groupBy('wip.Package_Name', 'plref.production_line');

    $f1Query = $this->applyDateOrWorkweekFilter($f1Query, 'wip', $useWorkweek, $workweekArray, $startDate, $endDate);

    // F2 query
    $f2Query = DB::table('customer_data_wip_sample as wip')
      ->select(
        'wip.Package_Name',
        'plref.production_line as PL',
        DB::raw('SUM(wip.Qty) as f2_total_quantity'),
        DB::raw('COUNT(DISTINCT wip.Lot_Id) as f2_total_lots')
      )
      ->join('ppc_productionline_packagereference as plref', 'wip.Package_Name', '=', 'plref.Package')
      ->where('wip.f2_focus_group_flag', 1)
      // ->whereIn('wip.Focus_Group', ['CV', 'LTI', 'LTCL', 'LT'])
      ->whereNotIn('wip.Station', ['GTTRES_T', 'GTGOUT', 'GTSUBCON', 'GTARCH_T', 'GTTBINLOC'])
      ->whereNotIn('wip.Part_Name', $specialPartNames)
      // ->whereBetween('wip.Date_Loaded', ["$startDate 00:00:00", "$endDate 23:59:59"])
      ->groupBy('wip.Package_Name', 'plref.production_line');

    $f2Query = $this->applyDateOrWorkweekFilter($f2Query, 'wip', $useWorkweek, $workweekArray, $startDate, $endDate);

    // F3 query
    $f3Query = DB::table('f3_data_wip as wip')
      ->select(
        'wip.Package_Name',
        'plref.production_line as PL',
        DB::raw('SUM(wip.Qty) as f3_total_quantity'),
        DB::raw('COUNT(DISTINCT wip.Lot_Id) as f3_total_lots')
      )
      ->join('ppc_productionline_packagereference as plref', 'wip.Package_Name', '=', 'plref.Package')
      // ->whereBetween('wip.Date_Loaded', ["$startDate 00:00:00", "$endDate 23:59:59"])
      ->groupBy('wip.Package_Name', 'plref.production_line');

    $f3Query = $this->applyDateOrWorkweekFilter($f3Query, 'wip', $useWorkweek, $workweekArray, $startDate, $endDate);

    $lfcspQuery = DB::table('customer_data_wip_sample as wip')
      ->select(
        DB::raw("'LFCSP' as Package_Name"),
        DB::raw("'PL1' as PL"),
        DB::raw('SUM(wip.Qty) as f1_total_quantity'),
        DB::raw('COUNT(DISTINCT wip.Lot_Id) as f1_total_lots')
      )
      ->whereIn('wip.Part_Name', $specialPartNames)
      // ->whereBetween('wip.Date_Loaded', ["$startDate 00:00:00", "$endDate 23:59:59"])
      ->where(function ($query) {
        $query
          // ->whereNotIn('wip.Focus_Group', ['CV', 'CV1', 'LT', 'LTCL', 'LTI'])
          ->where('wip.f1_focus_group_flag', 1)
          ->where('wip.Plant', '!=', 'ADPI')
          ->where(function ($q) {
            $q->whereIn('wip.Station', ['GTREEL', 'GTTRANS_B3'])
              ->orWhere(function ($q2) {
                $q2
                  // ->where('wip.Station', 'like', '%_T')
                  ->where('wip.station_suffix', '=', '_T')
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
      ->groupBy(DB::raw("'LFCSP'"))
      ->orderByDesc('f1_total_quantity');

    $lfcspQuery = $this->applyDateOrWorkweekFilter($lfcspQuery, 'wip', $useWorkweek, $workweekArray, $startDate, $endDate);

    //   })
    $allPackages = DB::table(DB::raw("({$f1Query->toSql()} UNION ALL {$lfcspQuery->toSql()}) as f1"))
      ->mergeBindings($f1Query)
      ->mergeBindings($lfcspQuery)
      ->leftJoin(DB::raw("({$f2Query->toSql()}) as f2"), function ($join) {
        $join->on('f1.Package_Name', '=', 'f2.Package_Name')
          ->on('f1.PL', '=', 'f2.PL');
      })
      ->mergeBindings($f2Query)
      ->leftJoin(DB::raw("({$f3Query->toSql()}) as f3"), function ($join) {
        $join->on('f1.Package_Name', '=', 'f3.Package_Name')
          ->on('f1.PL', '=', 'f3.PL');
      })
      ->mergeBindings($f3Query)
      ->select(
        'f1.Package_Name',
        'f1.PL',
        'f1.f1_total_quantity',
        'f1.f1_total_lots',
        'f2.f2_total_quantity',
        'f2.f2_total_lots',
        'f3.f3_total_quantity',
        'f3.f3_total_lots',
        DB::raw('(COALESCE(f1.f1_total_quantity,0) + COALESCE(f2.f2_total_quantity,0) + COALESCE(f3.f3_total_quantity,0)) as total_quantity')
        // DB::raw("CASE WHEN f1.Package_Name = 'LFCSP' AND f1.PL = 'PL1' THEN 1 ELSE 0 END as is_whole")
      )
      // ->orderBy('f1.PL')
      ->orderByDesc('total_quantity')
      ->get();

    return response()->json([
      'status' => 'success',
      'message' => 'Data retrieved successfully',
      'data' => $allPackages
    ]);
  }


  public function wipTable()
  {
    return Inertia::render('WIPTable');
  }
}
