<?php

namespace App\Repositories;

use App\Constants\WipConstants;
use App\Traits\NormalizeStringTrait;
use App\Traits\TrendAggregationTrait;
use App\Repositories\AnalogCalendarRepository;
use Illuminate\Support\Facades\Log;
use App\Helpers\SqlDebugHelper;
use App\Helpers\MergeAndAggregate;
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

    // $aliases = $this->packageGroupRepo->getMembersByPackageName($packageNames, $factory);
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

  public function buildTrend($factories, $packageName, $period, $startDate, $endDate, $workweeks, $aggregate = true)
  {
    $query = DB::table(self::F1F2_OUTS_TABLE . ' as wip');
    $query = $this->filterByPackageName($query, $packageName, $factories);

    if ($filter = $this->filterFactory($factories)) {
      $query->where($filter);
    }

    if ($aggregate) {
      $query = $this->applyTrendAggregation(
        $query,
        $period,
        $startDate,
        $endDate,
        'wip.date_loaded',
        ['SUM(wip.qty)' => 'total_outs'],
        workweeks: $workweeks
      );
    } else {
      $query->where('wip.date_loaded', '>=', $startDate)
        ->where('wip.date_loaded', '<', $endDate);
    }

    return $query;
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
    )->get();

    $f2Trend = $this->buildTrend(
      ['F2'],
      $packageName,
      $period,
      $startDate,
      $endDate,
      $workweeks
    )->get();

    $periodGroupBy = WipConstants::PERIOD_GROUP_BY[$period];
    $overallTrend = MergeAndAggregate::mergeAndAggregate([$f1Trend, $f2Trend], $periodGroupBy);

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

  // public function insertManyCustomers(array $data)
  // {
  //   $uniqueKeys = ['lot_id', 'date_loaded'];

  //   $allColumns = array_keys($data[0]);
  //   $updateColumns = array_diff($allColumns, $uniqueKeys);

  //   F1F2Out::upsert(
  //     $data,
  //     $uniqueKeys,
  //     $updateColumns
  //   );
  // }

  public function insertManyCustomers(array $data)
  {
    $data = array_map(function ($row) {
      if (isset($row['date_loaded'])) {
        $row['date_loaded'] = \Carbon\Carbon::parse($row['date_loaded'])->format('Y-m-d H:i:s');
      }
      return $row;
    }, $data);

    F1F2Out::insert($data);
  }
}
