<?php

namespace App\Repositories;

use App\Constants\WipConstants;
use App\Traits\TrendAggregation;
use App\Repositories\AnalogCalendarRepository;

use App\Models\F1F2Out;
use Illuminate\Support\Facades\DB;
use App\Helpers\WipTrendParser;

class F1F2OutRepository
{
  use TrendAggregation;
  protected $analogCalendarRepo;

  public function __construct(
    AnalogCalendarRepository $analogCalendarRepo,
  ) {
    $this->analogCalendarRepo = $analogCalendarRepo;
  }

  public const F1F2_OUTS_TABLE = "customer_data_wip_out";
  private const PPC_TABLE = "ppc_productionline_packagereference";

  public function getExistingRecords()
  {
    return DB::table(self::F1F2_OUTS_TABLE)
      ->select('lot_id', 'date_loaded')
      ->where('date_loaded', '>=', now()->subDays(28))
      ->get();
  }

  public function filterByPackageName($query, ?array $packageNames)
  {
    if (is_string($packageNames)) {
      $packageNames = explode(',', $packageNames);
    }
    $packageNames = array_filter((array) $packageNames, fn($p) => !empty($p));

    if (empty($packageNames)) return $query;

    return $query->whereIn('wip.package', $packageNames);
  }

  public function applyF2Filter($query, ?string $alias)
  {
    $prefix = $alias ? "{$alias}." : '';

    $query->whereIn("{$prefix}focus_group", WipConstants::F2_WIP_OUT_FOCUS_GROUP_INCLUSION);

    return $query;
  }
  public function applyF1Filter($query, ?string $alias)
  {
    $prefix = $alias ? "{$alias}." : '';

    $query->whereNotIn("{$prefix}focus_group", WipConstants::F1_WIP_OUT_FOCUS_GROUP_EXCLUSION);

    return $query;
  }

  public function joinPL($query, $partName = [], $joinPpc = '')
  {
    $query->join(self::PPC_TABLE . ' as plref', 'wip.package', '=', 'plref.Package');

    if ($joinPpc == 'PL1') {
      $query->where(function ($q) use ($partName) {
        $q->Where('plref.production_line', 'PL1')
          ->orwhereIn('wip.part_name', $partName);
      });
    }

    if ($joinPpc == 'PL6') {
      $query->where(function ($q) use ($partName) {
        $q->where('plref.production_line', 'PL6')
          ->whereNotIn('wip.part_name', $partName);
      });
    }

    return $query;
  }

  public function getF1QueryByPackage($packageName)
  {
    $f1QueryOuts = DB::table(self::F1F2_OUTS_TABLE . ' as wip');
    $f1QueryOuts = $this->applyF1Filter($f1QueryOuts, 'wip');
    $f1QueryOuts = $this->filterByPackageName($f1QueryOuts, $packageName);
    $f1QueryOuts = $this->joinPL($f1QueryOuts);

    return $f1QueryOuts;
  }

  public function getF2QueryByPackage($packageName)
  {
    $f2QueryOuts = DB::table(self::F1F2_OUTS_TABLE . ' as wip');
    $f2QueryOuts = $this->applyF2Filter($f2QueryOuts, 'wip');
    $f2QueryOuts = $this->filterByPackageName($f2QueryOuts, $packageName);
    $f2QueryOuts = $this->joinPL($f2QueryOuts);

    return $f2QueryOuts;
  }

  public function getF3QueryByPackage($packageName)
  {
    $f3QueryOuts = DB::table(self::F1F2_OUTS_TABLE . ' as wip');

    $f3QueryOuts->whereRaw('1 = 0');

    $f3QueryOuts = $this->joinPL($f3QueryOuts);

    return $f3QueryOuts;
  }

  public function getOverallTrend($packageName, $period, $lookBack, $offsetDays, $workweeks)
  {
    $query = DB::table(self::F1F2_OUTS_TABLE . ' as wip');
    $query = $this->filterByPackageName($query, $packageName);
    $query = $query->orderByDesc('total_outs');
    $query = $this->applyTrendAggregation(
      $query,
      $period,
      $lookBack,
      $offsetDays,
      'wip.date_loaded',
      ['SUM(wip.qty)' => 'total_outs'],
      workweeks: $workweeks
    )->get();

    $trends['overall_trend'] = $query;

    return WipTrendParser::parseTrendsByPeriod($trends);
  }

  public function insertCustomer(array $data)
  {
    F1F2Out::create($data);
  }

  public function insertManyCustomers(array $data)
  {
    F1F2Out::insert($data);
  }
}
