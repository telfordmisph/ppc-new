<?php

namespace App\Repositories;

use App\Traits\PackageAliasTrait;
use App\Traits\TrendAggregationTrait;
use App\Repositories\AnalogCalendarRepository;
use App\Repositories\PackageGroupRepository;
use App\Services\PackageFilters\PackageFilterService;
use App\Traits\F3Trait;
use App\Models\F3Out;
use App\Helpers\WipTrendParser;
use App\Helpers\SqlDebugHelper;
use Illuminate\Support\Facades\DB;
use App\Constants\WipConstants;
use Illuminate\Support\Facades\Log;

class F3OutRepository
{
  use TrendAggregationTrait;
  use PackageAliasTrait;
  use F3Trait;
  protected $table = "f3";
  protected $tableAlias = "f3";
  protected $packageGroupRepo;
  protected $analogCalendarRepo;
  protected $packageFilterService;
  public function __construct(
    PackageFilterService $packageFilterService,
    AnalogCalendarRepository $analogCalendarRepo,
    PackageGroupRepository $packageGroupRepo,
  ) {
    $this->analogCalendarRepo = $analogCalendarRepo;
    $this->packageGroupRepo = $packageGroupRepo;
    $this->packageFilterService = $packageFilterService;
  }
  public function getOverallTrend($packageNames, $period, $startDate, $endDate, $workweeks, $aggregate = true)
  {
    $query = $this->baseF3Query(type: 'out'); // 'out' instead of default 'wip'

    if ($aggregate) {
      $query = $this->applyTrendAggregation(
        $query,
        $period,
        $startDate,
        $endDate,
        'f3.date_loaded',
        ['SUM(f3.qty)' => 'total_outs'], // aggregate by outs
        workweeks: $workweeks
      );

      $query = $this->filterByPackageName($query, $packageNames, 'f3_pkg.package_name');
      $groupByOrderBy = WipConstants::PERIOD_GROUP_BY[$period];

      $results = DB::query()
        ->fromSub($query, 'combined');
      foreach ($groupByOrderBy as $col) {
        $results->orderBy($col);
      }

      $results = $results->orderByDesc('total_outs')
        ->get();

      $trends['overall_trend'] = $results;
      $trends['f3_trend'] = $results;

      return WipTrendParser::parseTrendsByPeriod($trends);
    } else {
      // No aggregation: just apply date filter
      $query->where('f3.date_loaded', '>=', $startDate)
        ->where('f3.date_loaded', '<', $endDate);

      $query->select(self::EXTERNAL_FILE_HEADERS);
      $query = $this->filterByPackageName($query, $packageNames, 'f3_pkg.package_name');

      $results = DB::query()
        ->fromSub($query, 'combined')
        ->get();

      return $results;
    }
  }

  public function insertManyCustomer(array $data)
  {
    F3Out::insert($data);
  }
}
