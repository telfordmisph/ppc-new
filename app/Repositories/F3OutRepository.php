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

  public function getOverallTrend($packageNames, $period, $startDate, $endDate, $workweeks)
  {
    $query = $this->baseF3Query(type: 'out');

    $query = $this->applyTrendAggregation(
      $query,
      $period,
      $startDate,
      $endDate,
      'f3.date_loaded',
      ['SUM(f3.qty)' => 'total_outs'],
      workweeks: $workweeks
    );
    $query = $this->filterByPackageName($query, $packageNames, 'f3_pkg.package_name');

    $groupByOrderBy = WipConstants::PERIOD_GROUP_BY[$period];

    // Log::info("F3 Out Overall Trend Query: " . SqlDebugHelper::prettify($unionQuery->toSql(), $unionQuery->getBindings()));

    $results = DB::query()
      ->fromSub($query, 'combined');
    foreach ($groupByOrderBy as $col) {
      $results->orderBy($col);
    }

    Log::info("F3 Outttt Overall Trend Query: " . SqlDebugHelper::prettify($results->toSql(), $results->getBindings()));

    $results = $results->orderByDesc('total_outs')
      ->get();



    Log::info("Results: " . json_encode($results));

    $trends['overall_trend'] = $results;
    $trends['f3_trend'] = $results;


    return WipTrendParser::parseTrendsByPeriod($trends);
  }

  public function insertManyCustomer(array $data)
  {
    F3Out::insert($data);
  }
}
