<?php

namespace App\Repositories;

use App\Constants\WipConstants;
use App\Traits\NormalizeStringTrait;
use App\Traits\TrendAggregationTrait;
use App\Repositories\AnalogCalendarRepository;
use Illuminate\Support\Facades\Log;
use App\Helpers\SqlDebugHelper;

use App\Models\F1F2Out;
use Illuminate\Support\Facades\DB;
use App\Helpers\WipTrendParser;
use App\Services\PackageFilters\PackageFilterService;
use App\Traits\PackageAliasTrait;

class F1F2OutRepository
{
  use NormalizeStringTrait;
  use TrendAggregationTrait;
  use PackageAliasTrait;
  protected $analogCalendarRepo;
  protected $packageFilterService;
  protected $packageGroupRepo;

  public function __construct(
    PackageFilterService $packageFilterService,
    PackageGroupRepository $packageGroupRepo,
    AnalogCalendarRepository $analogCalendarRepo,
  ) {
    $this->packageFilterService = $packageFilterService;
    $this->packageGroupRepo = $packageGroupRepo;
    $this->analogCalendarRepo = $analogCalendarRepo;
  }

  public const F1F2_OUTS_TABLE = "customer_data_wip_out";
  private const PPC_TABLE = "ppc_productionline_packagereference";

  public function getExistingRecords()
  {
    return DB::table(self::F1F2_OUTS_TABLE)
      ->select('lot_id', 'date_loaded')
      ->where('date_loaded', '>=', now()->subDays(WipConstants::DAYS_UNTIL_RECLASSIFIED_AS_NEW))
      ->get();
  }

  public function doesExist($column, $value)
  {
    return !DB::table(self::F1F2_OUTS_TABLE)
      ->where($column, $value)
      ->exists();
  }

  public function filterByPackageName($query, ?array $packageNames, $factories)
  {
    $query = $this->packageFilterService->applyPackageFilter($query, $packageNames, $factories, 'wip.package', "OUT");
    // if (is_string($packageNames)) {
    //   $packageNames = explode(',', $packageNames);
    // }
    // $packageNames = array_filter((array) $packageNames, fn($p) => !empty($p));
    // // separate alias for each packagename
    // Log::info("package names: " . json_encode($packageNames));

    // $aliases = $this->packageGroupRepo->getMembersByPackageName($packageNames, $factory);
    // Log::info("Aliases f1 f2 OUT : " . json_encode($aliases));
    // Log::info("factory: " . json_encode($factory));
    // if (!empty($aliases)) {
    //   $query->whereIn('wip.Package', $aliases);
    //   // $this->applyNormalizedDimensionFilter($query, $aliases->toArray(), 'wip.package');
    // }

    // package

    return $query;
  }

  public function applyF2Filter($query, ?string $alias)
  {
    $prefix = $alias ? "{$alias}." : '';

    $query->whereIn("{$prefix}focus_group", WipConstants::F2_OUT_FOCUS_GROUP_INCLUSION);

    return $query;
  }
  public function applyF1Filter($query, ?string $alias)
  {
    $prefix = $alias ? "{$alias}." : '';

    $query->whereNotIn("{$prefix}focus_group", WipConstants::F1_OUT_FOCUS_GROUP_EXCLUSION);

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
    $f1QueryOuts = $this->filterByPackageName($f1QueryOuts, $packageName, ['f1']);
    $f1QueryOuts = $this->joinPL($f1QueryOuts);

    return $f1QueryOuts;
  }

  public function getF2QueryByPackage($packageName)
  {
    $f2QueryOuts = DB::table(self::F1F2_OUTS_TABLE . ' as wip');
    $f2QueryOuts = $this->applyF2Filter($f2QueryOuts, 'wip');
    $f2QueryOuts = $this->filterByPackageName($f2QueryOuts, $packageName, ['f2']);
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

  public function filterFactory($factories = null)
  {
    if (in_array("F1", $factories)) {

      return fn($q) => $q->whereNotIn('wip.focus_group', WipConstants::F1_OUT_FOCUS_GROUP_EXCLUSION);
    }

    if (in_array("F2", $factories)) {
      return fn($q) => $q->whereIn('wip.focus_group', WipConstants::F2_OUT_FOCUS_GROUP_INCLUSION);
    }

    return null;
  }

  private function buildTrend($factories, $packageName, $period, $startDate, $endDate, $workweeks)
  {
    $query = DB::table(self::F1F2_OUTS_TABLE . ' as wip');
    $query = $this->filterByPackageName($query, $packageName, $factories);

    if ($filter = $this->filterFactory($factories)) {
      $query->where($filter);
    }

    $query = $this->applyTrendAggregation(
      $query,
      $period,
      $startDate,
      $endDate,
      'wip.date_loaded',
      ['SUM(wip.qty)' => 'total_outs'],
      workweeks: $workweeks
    );

    Log::info("F1F2 Out Overall Trend Query: " . SqlDebugHelper::prettify($query->toSql(), $query->getBindings()));
    Log::info("F1F2 Out Overall Trend Query: " . $query->toSql());

    return $query->get();
  }

  public function getOverallTrend($packageName, $period, $startDate, $endDate, $workweeks)
  {
    $f1Trend = $this->buildTrend(
      ['F1'],
      $packageName,
      $period,
      $startDate,
      $endDate,
      $workweeks
    );

    $f2Trend = $this->buildTrend(
      ['F2'],
      $packageName,
      $period,
      $startDate,
      $endDate,
      $workweeks
    );

    $overallTrend = $this->buildTrend(
      ['F1', 'F2'], // null means overall
      $packageName,
      $period,
      $startDate,
      $endDate,
      $workweeks
    );

    return WipTrendParser::parseTrendsByPeriod([
      'f1_trend' => $f1Trend,
      'f2_trend' => $f2Trend,
      'overall_trend' => $overallTrend,
    ]);
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
