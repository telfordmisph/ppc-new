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
use DateTime;

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

    $weekRange = $this->analogCalendarRepo->getDatesByWorkWeekRange($workweeks)['range'];

    foreach ($weekRange as $item) {
      $startDate = new DateTime($item->startDate);
      $endDate   = new DateTime($item->endDate);

      $startDate->modify('+1 day');
      $endDate->modify('+1 day');

      $item->startDate = $startDate->format('Y-m-d');
      $item->endDate   = $endDate->format('Y-m-d');
    }

    if ($aggregate) {
      $query = $this->applyTrendAggregation(
        $query,
        $period,
        $startDate,
        $endDate,
        'f3.date_loaded',
        ['SUM(f3.qty)' => 'total_outs'], // aggregate by outs
        workRange: $weekRange,
        isDateColumn: true
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

      if ($period == 'daily') {
        foreach ($results as $item) {
          $date = new DateTime($item->day);
          $date->modify('-1 day');
          $item->day = $date->format('Y-m-d');
        }
      }

      $trends['overall_trend'] = $results;
      $trends['f3_trend'] = $results;

      return WipTrendParser::parseTrendsByPeriod($trends);
    } else {
      $query->whereBetween('f3.date_loaded', [$startDate, $endDate]);

      $query->select(self::EXTERNAL_FILE_HEADERS);
      $query = $this->filterByPackageName($query, $packageNames, 'f3_pkg.package_name');

      $results = DB::query()
        ->fromSub($query, 'combined');
      // ->fromSub($query, 'combined')->get();

      return $results;
    }
  }

  public function insertManyCustomer(array $data)
  {
    F3Out::insert($data);
  }
}
