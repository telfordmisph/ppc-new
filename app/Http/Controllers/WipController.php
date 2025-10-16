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

  private function applyDateOrWorkweekFilter($query, $alias, $useWorkweek, $workweek, $startDate = null, $endDate = null)
  {
    if ($useWorkweek) {
      $workweekArray = array_map('intval', explode(' ', $workweek));

      $weekRanges = DB::table('analog_calendar')
        ->select(DB::raw('MIN(cal_date) as start_date'), DB::raw('MAX(cal_date) as end_date'))
        ->whereIn('cal_workweek', $workweekArray)
        ->groupBy('cal_workweek')
        ->get();

      return $query->where(function ($q) use ($alias, $weekRanges) {
        foreach ($weekRanges as $range) {
          $q->orWhereBetween("$alias.Date_Loaded", [$range->start_date, $range->end_date]);
        }
      });
    }

    return $query->whereBetween("$alias.Date_Loaded", [$startDate, $endDate]);
  }

  /**
   * Parse and validate a date range string.
   *
   * @param string $dateRange Format: "m/d/Y H:i:s - m/d/Y H:i:s"
   * @return array ['start' => 'Y-m-d H:i:s', 'end' => 'Y-m-d H:i:s']
   * @throws \Exception if invalid
   */
  private function parseDateRange(?string $dateRange = ''): array
  {
    if ($dateRange) {
      $dateParts = explode(' - ', $dateRange);

      if (count($dateParts) !== 2) {
        throw new \Exception('Date range must contain two parts.');
      }

      try {
        $start = Carbon::parse(trim($dateParts[0]))->startOfDay();
        $end   = Carbon::parse(trim($dateParts[1]))->endOfDay();
      } catch (\Exception $e) {
        throw new \Exception('Date parsing failed: ' . $e->getMessage());
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
    $today = now();
    return [
      'start' => $today->copy()->startOfDay()->format('Y-m-d H:i:s'),
      'end'   => $today->copy()->endOfDay()->format('Y-m-d H:i:s'),
    ];
  }

  private function applyFilterRules($query, $rules)
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
            ->orWhereNotIn('Station', ['GTTRANS_T', 'GTIQA_T', 'GTLPI_T', 'GTBKLDBE_T']);
        });
      }
    }

    return $query;
  }

  private function applyLineConditions($query, $filterType)
  {
    // Base station filter closure for reusability
    $f1Condition = function ($sub) {
      $sub->where('wip.f1_focus_group_flag', 1)
        ->where('wip.Plant', '!=', 'ADPI')
        ->where(function ($b) {
          $b->whereIn('wip.Station', ['GTREEL', 'GTTRANS_B3'])
            ->orWhere(function ($c) {
              $c->where('wip.station_suffix', '_T')
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
    };

    $f2Condition = function ($sub) {
      $sub->where('wip.f2_focus_group_flag', 1)
        ->whereNotIn('wip.Station', [
          'GTTRES_T',
          'GTSUBCON',
          'GTGOUT',
          'GTARCH_T',
          'GTTBINLOC'
        ]);
    };

    $query = match ($filterType) {
      'PL1' => $query
        ->join('ppc_productionline_packagereference as plref', 'wip.Package_Name', '=', 'plref.Package')
        ->where(function ($q) {
          $q->whereIn('wip.Part_Name', self::SPECIAL_PART_NAMES)
            ->orWhere('plref.production_line', 'PL1');
        }),

      'PL6' => $query
        ->join('ppc_productionline_packagereference as plref', 'wip.Package_Name', '=', 'plref.Package')
        ->whereNotIn('wip.Part_Name', self::SPECIAL_PART_NAMES)
        ->where('plref.production_line', 'PL6'),

      default => $query,
    };


    // Add focus group filters depending on the filterType
    return match ($filterType) {
      'F1' => $query->where($f1Condition),
      'F2' => $query->where($f2Condition),
      'PL1', 'PL6' => $query->where(function ($sub) use ($f1Condition, $f2Condition) {
        $sub->where($f1Condition)->orWhere($f2Condition);
      }),
      default => $query, // for 'All' or other filter types
    };
  }


  public function getWIPFilterSummary(Request $request)
  {
    try {
      $dateInput = trim($request->input('date', ''));
      $filterType = trim($request->input('filterType', ''));
      $filteringCondition = trim($request->input('filteringCondition', ''));

      if (empty($dateInput)) {
        throw new \Exception("Date is required");
      }

      [$startOfDay, $endOfDay] = $this->getDateRange($dateInput);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Invalid date provided: ' . $e->getMessage(),
      ], 422);
    }

    $filterRules = $this->getFilterRules();

    $query = $this->buildBaseQuery($filterType, $startOfDay, $endOfDay, $filterRules[$filteringCondition] ?? null);

    if ($filteringCondition === 'Bake') {
      $query = $this->addAdditionalBakeCondition($query, $startOfDay, $endOfDay, $filterType);
    }

    if (in_array($filteringCondition, ['All', 'Processable'])) {
      $f3Query = $this->buildF3Query($filterType, $startOfDay, $endOfDay);
      $combinedQuery = $query->unionAll($f3Query);
    } else {
      $combinedQuery = $query;
    }

    return DB::query()
      ->fromSub($combinedQuery, 'combined_results')
      ->selectRaw('SUM(total_qty) as final_total_qty, SUM(total_lots) as final_total_lots')
      ->first();
  }

  private function getDateRange($dateInput)
  {
    $date = Carbon::parse($dateInput);
    return [$date->copy()->startOfDay(), $date->copy()->endOfDay()];
  }

  private function buildBaseQuery($filterType, $startOfDay, $endOfDay, $rules)
  {
    $query = DB::table('customer_data_wip as wip')
      ->whereBetween('wip.Date_Loaded', [$startOfDay, $endOfDay]);

    $query = $this->applyLineConditions($query, $filterType);
    $query = $this->applyFilterRules($query, $rules);

    return $query->selectRaw('SUM(wip.Qty) as total_qty, COUNT(DISTINCT wip.Lot_Id) as total_lots');
  }

  private function addAdditionalBakeCondition($query, $startOfDay, $endOfDay, $filterType = null)
  {
    $mainBakeQuery = DB::table('customer_data_wip as wip')
      ->whereBetween('wip.Date_Loaded', [$startOfDay, $endOfDay])
      ->whereIn('wip.Stage', ['TBAKEL'])
      ->whereNotIn('wip.Lot_Status', ['HELD', 'RHLD', 'LWAITH'])
      ->where(function ($q) {
        $q->whereNotIn('wip.Stage', ['TBOXING', 'TBUYOFFQA', 'TOUTQA'])
          ->orWhereNotIn('wip.Station', ['GTTFVI_T']);
      });

    switch ($filterType) {
      case 'F2':
        $mainBakeQuery->where('wip.f2_focus_group_flag', 1)
          ->whereNotIn('wip.Station', [
            'GTTRES_T',
            'GTSUBCON',
            'GTGOUT',
            'GTARCH_T',
            'GTTBINLOC',
          ]);

        $query->whereNotIn('Station', [
          'GTLPI_T',
          'GTBRAND_T',
          'GTTERASE_T',
          'GTTLLI_T',
          'GTTSORT_T',
          'GTTRES_T',
          'GTSUBCON',
          'GTARCH_T',
          'GTTBINLOC',
        ])->whereIn('Station', [
          'GTTRANS_T',
          'GTIQA_T',
          'GTLPI_T',
          'GTBKLDBE_T',
        ]);
        break;

      case 'F1':
        $query->whereNotIn('Station', [
          'GTLPI_T',
          'GTBRAND_T',
          'GTTERASE_T',
          'GTTLLI_T',
          'GTTSORT_T',
          'GTTRANS_T',
          'GTIQA_T',
          'GTLPI_T',
          'GTBKLDBE_T',
        ]);
        break;

      case 'PL1':
      case 'PL2':
        $query->whereNotIn('Station', [
          'GTLPI_T',
          'GTBRAND_T',
          'GTTERASE_T',
          'GTTLLI_T',
          'GTTSORT_T',
        ])->whereIn('Station', [
          'GTTRANS_T',
          'GTIQA_T',
          'GTLPI_T',
          'GTBKLDBE_T',
        ]);
        break;
    }

    $mainBakeQuery->selectRaw('SUM(wip.Qty) AS total_qty, COUNT(DISTINCT wip.Lot_Id) AS total_lots');

    return $mainBakeQuery->unionAll($query);
  }


  private function buildF3Query($filterType, $startOfDay, $endOfDay)
  {
    return DB::table('f3_data_wip as f3')
      ->join('ppc_productionline_packagereference as plref_f3', 'f3.Package_Name', '=', 'plref_f3.Package')
      ->where('plref_f3.production_line', $filterType)
      ->whereBetween('f3.Date_Loaded', [$startOfDay, $endOfDay])
      ->selectRaw('SUM(f3.Qty) as total_qty, COUNT(DISTINCT f3.Lot_Id) as total_lots');
  }

  private function getFilterRules()
  {
    return [
      'All' => [],
      'Processable' => [
        'lot_status_not_in' => ['HELD', 'RHLD', 'LWAITH'],
        'stage_not_in' => ['TBOXING', 'TBUYOFFQA', 'TOUTQA', 'TBAKEL', 'TDREEL', 'TRANSFER'],
        'station_not_in' => ['GTTFVI_T', 'GTBRAND_T', 'GTTERASE_T', 'GTTSORT_T'],
        'is_for_bake' => false,
      ],
      'Hold' => [
        'lot_status_in' => ['HELD', 'RHLD', 'LWAITH'],
      ],
      'Pipeline' => [
        'lot_status_not_in' => ['HELD', 'RHLD', 'LWAITH'],
        'stage_in_or_station_in' => [
          'stage' => ['TBOXING', 'TBUYOFFQA', 'TOUTQA'],
          'station' => ['GTTFVI_T'],
        ],
      ],
      'Bake' => [
        'lot_status_not_in' => ['HELD', 'RHLD', 'LWAITH'],
        'is_for_bake' => true,
        'stage_not_in_or_station_not_in' => [
          'stage' => ['TBOXING', 'TBUYOFFQA', 'TOUTQA'],
          'station' => ['GTTFVI_T'],
        ],
        'stage_not_in' => ['TBAKEL', 'TDREEL', 'TRANSFER'],
      ],
      'Detapesegregation' => [
        'lot_status_not_in' => ['HELD', 'RHLD', 'LWAITH'],
        'stage_not_in_or_station_not_in' => [
          'stage' => ['TBOXING', 'TBUYOFFQA', 'TOUTQA'],
          'station' => ['GTTFVI_T'],
        ],
        'stage_not_in' => ['TBAKEL'],
        'stage_in' => ['TDREEL', 'TRANSFER'],
      ],
      'Lpi' => [
        'lot_status_not_in' => ['HELD', 'RHLD', 'LWAITH'],
        'stage_not_in_or_station_not_in' => [
          'stage' => ['TBOXING', 'TBUYOFFQA', 'TOUTQA'],
          'station' => ['GTTFVI_T'],
        ],
        'stage_not_in' => ['TBAKEL', 'TDREEL', 'TRANSFER'],
        'station_in' => ['GTLPI_T'],
      ],
      'Brand' => [
        'lot_status_not_in' => ['HELD', 'RHLD', 'LWAITH'],
        'stage_not_in_or_station_not_in' => [
          'stage' => ['TBOXING', 'TBUYOFFQA', 'TOUTQA'],
          'station' => ['GTTFVI_T'],
        ],
        'stage_not_in' => ['TBAKEL', 'TDREEL', 'TRANSFER'],
        'station_not_in' => ['GTLPI_T'],
        'station_in' => ['GTBRAND_T', 'GTTERASE_T'],
      ],
      'Lli' => [
        'lot_status_not_in' => ['HELD', 'RHLD', 'LWAITH'],
        'stage_not_in_or_station_not_in' => [
          'stage' => ['TBOXING', 'TBUYOFFQA', 'TOUTQA'],
          'station' => ['GTTFVI_T'],
        ],
        'stage_not_in' => ['TBAKEL', 'TDREEL', 'TRANSFER'],
        'station_not_in' => ['GTLPI_T', 'GTBRAND_T', 'GTTERASE_T'],
        'station_in' => ['GTTLLI_T'],
      ],
      'Sort' => [
        'lot_status_not_in' => ['HELD', 'RHLD', 'LWAITH'],
        'stage_not_in_or_station_not_in' => [
          'stage' => ['TBOXING', 'TBUYOFFQA', 'TOUTQA'],
          'station' => ['GTTFVI_T'],
        ],
        'stage_not_in' => ['TBAKEL', 'TDREEL', 'TRANSFER'],
        'station_not_in' => ['GTLPI_T', 'GTBRAND_T', 'GTTERASE_T'],
        'station_in' => ['GTTSORT_T'],
      ],
    ];
  }

  public function getTodayWip()
  {
    try {
      // Generate date range (last 7 days including today)
      $endDate = Carbon::now()->endOfDay();          // e.g. 2025-10-13 23:59:59
      $startDate = Carbon::now()->subDays(20)->startOfDay(); // e.g. 2025-09-23 00:00:00

      // --------------------------------
      // Fetch F3 Data
      // --------------------------------
      $f3DataRaw = DB::table('f3_data_wip')
        ->selectRaw('DATE(Date_Loaded) AS report_date, SUM(Qty) AS f3_wip')
        ->whereBetween('Date_Loaded', [$startDate, $endDate])
        ->groupBy(DB::raw('DATE(Date_Loaded)'))
        ->get();

      // Convert into keyed array for easy lookup
      $f3Data = [];
      foreach ($f3DataRaw as $row) {
        $f3Data[$row->report_date] = (int) $row->f3_wip;
      }

      $f1Data = DB::table('customer_data_wip')
        ->selectRaw('DATE(Date_Loaded) AS report_date')
        ->selectRaw('SUM(Qty) AS f1_wip')
        ->where('Plant', '!=', 'ADPI')
        ->whereNotIn('Focus_Group', ['CV', 'CV1', 'LT', 'LTCL', 'LTI', 'DLT', 'WLT', 'SOF'])
        ->whereBetween('Date_Loaded', [$startDate, $endDate])
        ->where(function ($query) {
          $query->whereIn('Station', [
            'GTREEL',
            'CVDTRAN_GT',
            'GTARCH_T',
            'GTTRANS_BE',
            'GTTRES_T',
            'PITBOX_T',
            'PITBOX1',
            'PITFVI1',
            'PITLABEL1',
            'PITOQA',
            'PITOQA1',
            'Q-PITRANS1'
          ])
            ->orWhere(function ($q) {
              $q->where('station_suffix', '=', '_T')
                ->whereNotIn('Station', [
                  'GTSUBCON',
                  'GTTBINLOC',
                  'GTRANS_BOX',
                  'GTTRANS_QA',
                  'GTBRAND',
                  'GTGOUT',
                  'GTTBOX',
                  'GTTFVI',
                  'GTTOQA',
                  'GTTRANS_B3'
                ]);
            });
        })
        ->groupBy(DB::raw('DATE(Date_Loaded)'));

      $f2Data = DB::table('customer_data_wip')
        ->selectRaw('DATE(Date_Loaded) AS report_date')
        ->selectRaw('SUM(Qty) AS f2_wip')
        ->where('f2_focus_group_flag', 1)
        ->whereNotIn('Station', [
          'GTTRES_T',
          'GTGOUT',
          'GTSUBCON',
          'GTARCH_T',
          'GTTBINLOC',
          'Q-PITRANS1',
          'GTTRANS_BE'
        ])
        ->whereBetween('Date_Loaded', [$startDate, $endDate])
        ->groupBy(DB::raw('DATE(Date_Loaded)'));

      Log::info($f1Data->get());
      Log::info($f2Data->get());

      $f1f2Data = DB::query()
        ->fromSub($f1Data, 'f1')
        ->joinSub($f2Data, 'f2', function ($join) {
          $join->on('f1.report_date', '=', 'f2.report_date');
        })
        ->select('f1.report_date', 'f1.f1_wip', 'f2.f2_wip')
        ->get();


      // $f1f2Data = DB::table(DB::raw("({$f1Data->toSql()}) as f1"))
      //   ->mergeBindings($f1Data)
      //   ->joinSub($f2Data, 'f2', function ($join) {
      //     $join->on('f1.report_date', '=', 'f2.report_date');
      //   })
      //   ->select('f1.report_date', 'f1.f1_wip', 'f2.f2_wip')
      //   ->get();



      // --------------------------------
      // Combine Results
      // --------------------------------
      $data = [];
      foreach ($f1f2Data as $row) {
        $reportDate = $row->report_date;
        $f3Value = $f3Data[$reportDate] ?? 0;

        Log::info("Using " . ($reportDate) . " filter");

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
    $workweek  = $request->input('workweek', '');

    $startDate = "";
    $endDate   = "";

    $useWorkweek = !empty($workweek);

    if (!$useWorkweek) {
      try {
        $range = $this->parseDateRange($request->input('dateRange', ''));

        $startDate = Carbon::parse($range['start'])->startOfDay();
        $endDate   = Carbon::parse($range['end'])->endOfDay();

        Log::info("Start Date: $startDate, End Date: $endDate");
      } catch (\Exception $e) {
        return response()->json([
          'status'  => 'error',
          'message' => 'Invalid date format: ' . $e->getMessage(),
        ], 400);
      }
    }

    Log::info("Using " . ($useWorkweek ? "workweek" : "date range") . " filter");
    Log::info("Start Date: $startDate, End Date: $endDate");


    // $f1Query = DB::table('customer_data_wip_sample as wip')
    $f1Query = DB::table('customer_data_wip as wip')
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

    $f1Query = $this->applyDateOrWorkweekFilter($f1Query, 'wip', $useWorkweek, $workweek, $startDate, $endDate);
    $f1QueryResult = $f1Query->first();

    // $f2Query = DB::table('customer_data_wip_sample as wip')
    $f2Query = DB::table('customer_data_wip as wip')
      ->select(
        DB::raw('SUM(wip.Qty) AS f2_total_quantity'),
      )
      ->where('wip.f2_focus_group_flag', 1)
      ->whereNotIn('wip.Station', self::EXCLUDED_STATIONS_F2);

    $f2Query = $this->applyDateOrWorkweekFilter($f2Query, 'wip', $useWorkweek, $workweek, $startDate, $endDate);
    $f2QueryResult = $f2Query->first();

    // $f1pl6Query = DB::table('customer_data_wip_sample as wip')
    $f1pl6Query = DB::table('customer_data_wip as wip')
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

    $f1pl6Query = $this->applyDateOrWorkweekFilter($f1pl6Query, 'wip', $useWorkweek, $workweek, $startDate, $endDate);
    $f1pl6QueryResult = $f1pl6Query->first();

    // $f1pl1Query = DB::table('customer_data_wip_sample as wip')
    $f1pl1Query = DB::table('customer_data_wip as wip')
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

    $f1pl1Query = $this->applyDateOrWorkweekFilter($f1pl1Query, 'wip', $useWorkweek, $workweek, $startDate, $endDate);
    $f1pl1QueryResult = $f1pl1Query->first();

    // $f2pl6Query = DB::table('customer_data_wip_sample as wip')
    $f2pl6Query = DB::table('customer_data_wip as wip')
      ->select(DB::raw('SUM(wip.Qty) AS f2pl6_total_quantity'))
      ->join('ppc_productionline_packagereference as p', 'wip.Package_Name', '=', 'p.Package')
      ->where('wip.f2_focus_group_flag', 1)
      ->whereNotIn('wip.Station', ['GTTRES_T', 'GTGOUT', 'GTSUBCON', 'GTARCH_T', 'GTTBINLOC'])
      ->whereNotIn('wip.Part_Name', self::SPECIAL_PART_NAMES)
      ->Where('p.production_line', 'PL6');

    $f2pl6Query = $this->applyDateOrWorkweekFilter($f2pl6Query, 'wip', $useWorkweek, $workweek, $startDate, $endDate);
    $f2pl6QueryResult = $f2pl6Query->first();

    // $f2pl1Query = DB::table('customer_data_wip_sample as wip')
    $f2pl1Query = DB::table('customer_data_wip as wip')
      ->select(DB::raw('SUM(wip.Qty) AS f2pl1_total_quantity'))
      ->join('ppc_productionline_packagereference as p', 'wip.Package_Name', '=', 'p.Package')
      ->where('wip.f2_focus_group_flag', 1)
      ->whereNotIn('wip.Station', self::EXCLUDED_STATIONS_F2)
      ->where(function ($query) {
        $query->whereIn('wip.Part_Name', self::SPECIAL_PART_NAMES)
          ->orWhere('p.production_line', 'PL1');
      });

    $f2pl1Query = $this->applyDateOrWorkweekFilter($f2pl1Query, 'wip', $useWorkweek, $workweek, $startDate, $endDate);
    $f2pl1QueryResult = $f2pl1Query->first();

    $f3Total = DB::table('f3_data_wip as f3')
      ->where('f3.Focus_Group', 'F3');

    $f3Total = $this->applyDateOrWorkweekFilter($f3Total, 'f3', $useWorkweek, $workweek, $startDate, $endDate)
      ->selectRaw("SUM(f3.Qty) AS f3_total_quantity")
      ->first();

    $f3PlTotals = DB::table('f3_data_wip as f3')
      ->join('ppc_productionline_packagereference as ref', 'f3.Package_Name', '=', 'ref.Package')
      ->where('f3.Focus_Group', 'F3');

    $f3PlTotals = $this->applyDateOrWorkweekFilter($f3PlTotals, 'f3', $useWorkweek, $workweek, $startDate, $endDate)
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
      'total_f2_pl6' => (int) $f2pl6QueryResult->f2pl6_total_quantity,
      'total_f3_pl1' => (int) $f3PlTotals->f3pl1_total_quantity,
      'total_f3_pl6' => (int) $f3PlTotals->f3pl6_total_quantity,
      'total_quantity' => $grandTotal,
      'status' => 'success',
      'message' => 'Data retrieved successfully'
    ]);
  }

  public function getOverallPickUp(Request $request)
  {
    try {
      $range = $this->parseDateRange($request->input('dateRange', ''));
      $startDate = $range['start'];
      $endDate   = $range['end'];
    } catch (\Exception $e) {
      return response()->json([
        'status'  => 'error',
        'message' => 'Invalid date format: ' . $e->getMessage(),
      ], 400);
    }

    $partSubquery = DB::table('ppc_partnamedb')
      ->select('Partname', 'Factory', 'PL')
      ->distinct();

    $result = new \stdClass();

    $result->total_quantity = DB::table('ppc_pickupdb')
      ->whereBetween('DATE_CREATED', [$startDate, $endDate])
      ->sum('QTY');

    foreach (['F1', 'F2', 'F3'] as $factory) {
      $key = strtolower($factory) . '_total_quantity';
      $result->{$key} = DB::table('ppc_pickupdb as p')
        ->joinSub(DB::table('ppc_partnamedb')
          ->select('Partname', 'Factory')
          ->distinct(), 'part', function ($join) {
          $join->on('p.PARTNAME', '=', 'part.Partname');
        })
        ->whereBetween('p.DATE_CREATED', [$startDate, $endDate])
        ->where('part.Factory', $factory)
        ->sum('p.QTY');
    }

    $pl_list = ['PL1', 'PL6'];
    foreach (['F1', 'F2', 'F3'] as $factory) {
      foreach ($pl_list as $pl) {
        $key = strtolower($factory) . strtolower($pl) . '_total_quantity';
        $result->{$key} = DB::table('ppc_pickupdb as p')
          ->joinSub($partSubquery, 'part', function ($join) {
            $join->on('p.PARTNAME', '=', 'part.Partname');
          })
          ->whereBetween('p.DATE_CREATED', [$startDate, $endDate])
          ->where('part.Factory', $factory)
          ->where('part.PL', $pl)
          ->sum('p.QTY');
      }
    }

    $pl1_total_quantity = (int) $result->f1pl1_total_quantity + (int) $result->f2pl1_total_quantity + (int) $result->f3pl1_total_quantity;
    $pl6_total_quantity = (int) $result->f1pl6_total_quantity + (int) $result->f2pl6_total_quantity + (int) $result->f3pl6_total_quantity;

    return response()->json([
      'total_quantity' => (int) $result->total_quantity,
      'f1_total_quantity' => (int) $result->f1_total_quantity,
      'f2_total_quantity' => (int) $result->f2_total_quantity,
      'f3_total_quantity' => (int) $result->f3_total_quantity,
      'total_f1_pl1' => (int) $result->f1pl1_total_quantity,
      'total_f1_pl6' => (int) $result->f1pl6_total_quantity,
      'total_f2_pl1' => (int) $result->f2pl1_total_quantity,
      'total_f2_pl6' => (int) $result->f2pl6_total_quantity,
      'total_f3_pl1' => (int) $result->f3pl1_total_quantity,
      'total_f3_pl6' => (int) $result->f3pl6_total_quantity,
      'total_pl1' => $pl1_total_quantity,
      'total_pl6' => $pl6_total_quantity,
      'status' => 'success',
      'message' => 'Data retrieved successfully'
    ]);
  }

  public function getOverallResidual(Request $request)
  {
    try {
      $range = $this->parseDateRange($request->input('dateRange', ''));
      $startDate = $range['start'];
      $endDate   = $range['end'];
    } catch (\Exception $e) {
      return response()->json([
        'status'  => 'error',
        'message' => 'Invalid date format: ' . $e->getMessage(),
      ], 400);
    }

    $result = new \stdClass();

    $fBaseQuery = DB::table('customer_data_wip as wip')
      ->whereBetween('wip.Date_Loaded', [$startDate, $endDate])
      ->where('wip.Station', 'GTTRES_T');

    $result->f1_total_quantity = (clone $fBaseQuery)
      ->where('wip.f1_focus_group_flag', 1)
      ->where('wip.Plant', '!=', 'ADPI')
      ->sum('QTY');

    $result->f2_total_quantity = (clone $fBaseQuery)
      ->where('wip.f2_focus_group_flag', 1)
      ->sum('QTY');

    $result->total_quantity = $result->f1_total_quantity + $result->f2_total_quantity;

    $plBaseQuery = DB::table('customer_data_wip as c')
      ->join('ppc_productionline_packagereference as p', 'c.Package_Name', '=', 'p.Package')
      ->whereBetween('c.Date_Loaded', [$startDate, $endDate])
      ->where('c.Station', 'GTTRES_T');

    $result->f1pl1_total_quantity = (clone $plBaseQuery)
      ->where('f1_focus_group_flag', 1)
      ->where('Plant', '!=', 'ADPI')
      ->where(function ($query) {
        $query->whereIn('c.Part_Name', self::SPECIAL_PART_NAMES)
          ->orWhere('p.production_line', 'PL1');
      })
      ->sum('Qty');

    $result->f2pl1_total_quantity = (clone $plBaseQuery)
      ->where('f2_focus_group_flag', 1)
      ->where(function ($query) {
        $query->where('p.production_line', 'PL1')
          ->orWhereIn('c.Part_Name', self::SPECIAL_PART_NAMES);
      })
      ->sum('Qty');

    $result->f1pl6_total_quantity = (clone $plBaseQuery)
      ->where('p.production_line', 'PL6')
      ->where('f1_focus_group_flag', 1)
      ->where('Plant', '!=', 'ADPI')
      ->whereNotIn('c.Part_Name', self::SPECIAL_PART_NAMES)
      ->sum('Qty');

    $result->f2pl6_total_quantity = (clone $plBaseQuery)
      ->where('p.production_line', 'PL6')
      ->where('f2_focus_group_flag', 1)
      ->whereNotIn('c.Part_Name', self::SPECIAL_PART_NAMES)
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

  public function getPackageResidualSummary(Request $request)
  {
    try {
      $range = $this->parseDateRange($request->input('dateRange', ''));
      $startDate = $range['start'];
      $endDate   = $range['end'];
    } catch (\Exception $e) {
      return response()->json([
        'status'  => 'error',
        'message' => 'Invalid date format: ' . $e->getMessage(),
      ], 400);
    }

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
        $query = DB::table('customer_data_wip as wip')
          ->selectRaw('wip.Package_Name AS package_name, SUM(Qty) AS total_quantity, COUNT(DISTINCT Lot_Id) AS total_lots')
          ->where('wip.Station', 'GTTRES_T')
          ->whereBetween('wip.Date_Loaded', [$startDate, $endDate]);

        if ($chartStatus === 'all') {
          $query->where(function ($q) {
            $q->where(function ($sub) {
              $sub->where('wip.f1_focus_group_flag', 1)
                ->where('wip.Plant', '!=', 'ADPI');
            })
              ->orWhere(function ($sub) {
                $sub->where('wip.f2_focus_group_flag', 1);
                // TODO: 'GTTRES_T', not reallt included?
              });
          });
        }

        $query->groupBy('package_name')
          ->orderByDesc('total_quantity');
        break;

      case 'F1':
        $query = DB::table('customer_data_wip as wip')
          ->selectRaw('wip.Package_Name AS package_name, SUM(Qty) AS total_quantity, COUNT(DISTINCT Lot_Id) AS total_lots')
          ->where('wip.Station', 'GTTRES_T')
          ->whereBetween('wip.Date_Loaded', [$startDate, $endDate]);
        $query->where(function ($q) {
          $q->where(function ($sub) {
            $sub->where('wip.f1_focus_group_flag', 1)
              ->where('wip.Plant', '!=', 'ADPI');
          });
        });

        $query->groupBy('package_name')
          ->orderByDesc('total_quantity');
        break;

      // ! ON GOING PROJECT (I don't know if whether or not this meant that it's still in development (missing features))
      case 'F2':
      case 'F3':
      case 'PL1':
      case 'PL6':
        $query = DB::table('ppc_pickupdb')
          ->selectRaw('ppc_pickupdb.PACKAGE AS package_name, SUM(QTY) AS total_quantity, COUNT(DISTINCT LOTID) AS total_lots')
          ->join('ppc_partnamedb as partname', 'ppc_pickupdb.PARTNAME', '=', 'partname.Partname')
          ->whereBetween('ppc_pickupdb.DATE_CREATED', [$startDate, $endDate]);

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

    $results = $query->get();

    return response()->json([
      'data' => $results,
      'status' => 'success',
      'message' => 'Data retrieved successfully'
    ]);
  }


  public function getPackagePickUpSummary(Request $request)
  {
    try {
      $range = $this->parseDateRange($request->input('dateRange', ''));
      $startDate = $range['start'];
      $endDate   = $range['end'];
    } catch (\Exception $e) {
      return response()->json([
        'status'  => 'error',
        'message' => 'Invalid date format: ' . $e->getMessage(),
      ], 400);
    }

    $allowedChartStatuses = ['all', 'F1', 'F2', 'F3', 'PL1', 'PL6'];
    $chartStatus = $request->input('chartStatus', 'all');

    if (!in_array($chartStatus, $allowedChartStatuses)) {
      return response()->json([
        'status'  => 'error',
        'message' => 'Invalid chart status: ' . $chartStatus,
      ], 400);
    }

    $query = DB::table('ppc_pickupdb as p')
      ->select(
        'p.PACKAGE',
        DB::raw('SUM(p.QTY) as total_quantity'),
        DB::raw('COUNT(DISTINCT p.LOTID) as total_lots')
      );

    $query->whereBetween('p.DATE_CREATED', [$startDate, $endDate]);

    switch ($chartStatus) {
      case 'all':
        break;

      case 'F1':
      case 'F2':
      case 'F3':
        $query->join('ppc_partnamedb as part', 'p.PARTNAME', '=', 'part.Partname')
          ->where('part.Factory', $chartStatus);
        break;

      case 'PL1':
      case 'PL6':
        $query->join('ppc_partnamedb as part', 'p.PARTNAME', '=', 'part.Partname')
          ->where('part.PL', $chartStatus);
        break;

      default:
        $query->whereRaw('DATE(p.DATE_CREATED) = CURDATE()');
        break;
    }

    $results = $query
      ->groupBy('p.PACKAGE')
      ->orderByDesc('total_quantity')
      ->get();

    return response()->json([
      'data' => $results,
      'status' => 'success',
      'message' => 'Data retrieved successfully'
    ]);
  }

  public function getWIPQuantityAndLotsTotal(Request $request)
  {
    try {
      $workweek  = $request->input('workweek', '');

      $startDate = "";
      $endDate   = "";

      $useWorkweek = !empty($workweek);

      if (!$useWorkweek) {
        try {
          $range = $this->parseDateRange($request->input('dateRange', ''));

          $startDate = Carbon::parse($range['start'])->startOfDay();
          $endDate   = Carbon::parse($range['end'])->endOfDay();

          Log::info("Start Date: $startDate, End Date: $endDate");
        } catch (\Exception $e) {
          return response()->json([
            'status'  => 'error',
            'message' => 'Invalid date format: ' . $e->getMessage(),
          ], 400);
        }
      }

      // $f1Query = DB::table('customer_data_wip_sample as wip')
      $f1Query = DB::table('customer_data_wip as wip')
        ->select(
          'wip.Package_Name',
          DB::raw('SUM(wip.Qty) AS f1_total_quantity'),
          DB::raw('COUNT(DISTINCT wip.Lot_Id) AS total_lots')
        )
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

      $f1Query = $this->applyDateOrWorkweekFilter($f1Query, 'wip', $useWorkweek, $workweek, $startDate, $endDate);

      // $f2Query = DB::table('customer_data_wip_sample as wip')
      $f2Query = DB::table('customer_data_wip as wip')
        ->select(
          'wip.Package_Name',
          DB::raw('SUM(wip.Qty) AS f2_total_quantity'),
          DB::raw('COUNT(DISTINCT wip.Lot_Id) AS total_lots')
        )
        ->where('wip.f2_focus_group_flag', 1)
        ->whereNotIn('wip.Station', [
          'GTTRES_T',
          'GTSUBCON',
          'GTGOUT',
          'GTARCH_T',
          'GTTBINLOC'
        ])
        ->groupBy('wip.Package_Name');

      $f2Query = $this->applyDateOrWorkweekFilter($f2Query, 'wip', $useWorkweek, $workweek, $startDate, $endDate);

      $f3 = DB::table('f3_data_wip as f3')
        ->select(
          'f3.Package_Name',
          DB::raw('SUM(f3.Qty) AS f3_total_quantity'),
          DB::raw('COUNT(DISTINCT f3.Lot_Id) AS total_lots')
        )
        ->where('f3.Focus_Group', '=', 'F3')
        ->groupBy('f3.Package_Name');

      $f3 = $this->applyDateOrWorkweekFilter($f3, 'f3', $useWorkweek, $workweek, $startDate, $endDate);

      // $total_lots = DB::table('customer_data_wip_sample as wip')
      $total_lots = DB::table('customer_data_wip as wip')
        ->select(
          'wip.Package_Name',
          DB::raw('COUNT(DISTINCT wip.Lot_Id) AS total_lots')
        )
        ->groupBy('wip.Package_Name');

      $total_lots = $this->applyDateOrWorkweekFilter($total_lots, 'wip', $useWorkweek, $workweek, $startDate, $endDate);


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
    $workweek  = $request->input('workweek', '');

    $startDate = "";
    $endDate   = "";

    $useWorkweek = !empty($workweek);

    if (!$useWorkweek) {
      try {
        $range = $this->parseDateRange($request->input('dateRange', ''));

        $startDate = Carbon::parse($range['start'])->startOfDay();
        $endDate   = Carbon::parse($range['end'])->endOfDay();

        Log::info("Start Date: $startDate, End Date: $endDate");
      } catch (\Exception $e) {
        return response()->json([
          'status'  => 'error',
          'message' => 'Invalid date format: ' . $e->getMessage(),
        ], 400);
      }
    }

    // F1 query (PL1 + PL6)
    // $f1Query = DB::table('customer_data_wip_sample as wip')
    $f1Query = DB::table('customer_data_wip as wip')
      ->select(
        'wip.Package_Name',
        'plref.production_line as PL',
        DB::raw('SUM(wip.Qty) as f1_total_quantity'),
        DB::raw('COUNT(DISTINCT wip.Lot_Id) as f1_total_lots')
      )
      ->join('ppc_productionline_packagereference as plref', 'wip.Package_Name', '=', 'plref.Package')
      ->whereNotIn('wip.Part_Name', self::SPECIAL_PART_NAMES)
      ->where(function ($q) {
        $q
          ->where('wip.f1_focus_group_flag', 1)
          ->where('wip.Plant', '!=', 'ADPI')
          ->where(function ($sub) {
            $sub->whereIn('wip.Station', ['GTREEL', 'GTTRANS_B3'])
              ->orWhere(function ($s) {
                $s
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
      ->groupBy('wip.Package_Name', 'plref.production_line');

    $f1Query = $this->applyDateOrWorkweekFilter($f1Query, 'wip', $useWorkweek, $workweek, $startDate, $endDate);

    // F2 query
    // $f2Query = DB::table('customer_data_wip_sample as wip')
    $f2Query = DB::table('customer_data_wip as wip')
      ->select(
        'wip.Package_Name',
        'plref.production_line as PL',
        DB::raw('SUM(wip.Qty) as f2_total_quantity'),
        DB::raw('COUNT(DISTINCT wip.Lot_Id) as f2_total_lots')
      )
      ->join('ppc_productionline_packagereference as plref', 'wip.Package_Name', '=', 'plref.Package')
      ->where('wip.f2_focus_group_flag', 1)
      ->whereNotIn('wip.Station', ['GTTRES_T', 'GTGOUT', 'GTSUBCON', 'GTARCH_T', 'GTTBINLOC'])
      ->whereNotIn('wip.Part_Name', self::SPECIAL_PART_NAMES)
      ->groupBy('wip.Package_Name', 'plref.production_line');

    $f2Query = $this->applyDateOrWorkweekFilter($f2Query, 'wip', $useWorkweek, $workweek, $startDate, $endDate);

    // F3 query
    $f3Query = DB::table('f3_data_wip as wip')
      ->select(
        'wip.Package_Name',
        'plref.production_line as PL',
        DB::raw('SUM(wip.Qty) as f3_total_quantity'),
        DB::raw('COUNT(DISTINCT wip.Lot_Id) as f3_total_lots')
      )
      ->join('ppc_productionline_packagereference as plref', 'wip.Package_Name', '=', 'plref.Package')
      ->groupBy('wip.Package_Name', 'plref.production_line');

    $f3Query = $this->applyDateOrWorkweekFilter($f3Query, 'wip', $useWorkweek, $workweek, $startDate, $endDate);

    // $lfcspQuery = DB::table('customer_data_wip_sample as wip')
    $lfcspQuery = DB::table('customer_data_wip as wip')
      ->select(
        DB::raw("'LFCSP' as Package_Name"),
        DB::raw("'PL1' as PL"),
        DB::raw('SUM(wip.Qty) as f1_total_quantity'),
        DB::raw('COUNT(DISTINCT wip.Lot_Id) as f1_total_lots')
      )
      ->whereIn('wip.Part_Name', self::SPECIAL_PART_NAMES)
      ->where(function ($query) {
        $query
          ->where('wip.f1_focus_group_flag', 1)
          ->where('wip.Plant', '!=', 'ADPI')
          ->where(function ($q) {
            $q->whereIn('wip.Station', ['GTREEL', 'GTTRANS_B3'])
              ->orWhere(function ($q2) {
                $q2
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

    $lfcspQuery = $this->applyDateOrWorkweekFilter($lfcspQuery, 'wip', $useWorkweek, $workweek, $startDate, $endDate);

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
