<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataController extends Controller
{
  public function getTotalQuantity()
  {
    $total = DB::table('f3_data_wip')->sum('Qty');
    return response()->json(['total_quantity' => $total]);
  }

  public function getWIP()
  {
    $excludedStations = [
      'gttres_t',
      'gtarch_t',
      'gtcarier',
      'gtbrand',
      'gtgout',
      'gtrans-box',
      'gtsubcon',
      'gttbinloc',
      'gttbox',
      'gttfvi',
      'gttoqa',
      'gttpack',
      'gtrans_b3',
      'gtrans_qa',
      'pi-gttrans',
      'cvdtran_gt'
    ];

    $results = DB::table('customer_data_wip')
      ->selectRaw("
        CASE
            WHEN UPPER(Focus_Group) IN ('CV','LT','LTCL','LTI') THEN 'F2'
            ELSE 'F1'
        END AS wip_group,
        SUM(Qty) AS total_qty
    ")
      ->whereNotNull('Qty')
      ->where('Qty', '>', 0)
      ->whereRaw('LOWER(Plant) <> ?', ['adpi'])
      ->whereNotIn(DB::raw('LOWER(Station)'), $excludedStations)
      ->whereNotIn(DB::raw('UPPER(Focus_Group)'), ['CVI', 'LTCI', 'DLT'])
      ->whereNotIn(DB::raw('UPPER(Lot_Status)'), ['HELD', 'RHELD', 'LWAITH'])
      ->whereNotIn(DB::raw('UPPER(Stage)'), ['TBAKEL', 'TBOXING', 'TOUTQA', 'TBUYOFFQA', 'TDEREEL'])
      ->whereNotIn(DB::raw('UPPER(Station)'), ['GTTFVI_T', 'GTBRAND_T', 'GTTSORT_T', 'GTTLLI_T', 'GTLPI_T'])
      ->where(function ($q) {
        $q->whereNotNull('Ramp_Time')
          ->orWhere('Ramp_Time', '!=', '');
      })
      ->where(function ($q) {
        $q->where('Bake', '!=', 'FOR BAKE')
          ->orWhere('Bake_Count', '!=', 0);
      })
      ->groupBy('wip_group')
      ->get();

    return response()->json(['data' => $results]);
  }
}