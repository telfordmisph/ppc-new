<?php

namespace App\Repositories;

use App\Traits\ApplyDateOrWorkWeekFilter;
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
use App\Traits\ShiftObjectDates;
use Illuminate\Support\Facades\Log;
use DateTime;

class F3OutRepository
{
  use TrendAggregationTrait;
  use ApplyDateOrWorkWeekFilter;
  use PackageAliasTrait;
  use F3Trait;
  use ShiftObjectDates;
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

  public function baseF3OutQuery($joinPpc = false)
  {
    return $this->baseF3Query(joinPpc: $joinPpc, type: 'out');
  }

  private function overallByDate(?string $joinPpc, bool $useWorkweek, $workweek, string $startDate, string $endDate)
  {
    $query = $this->baseF3OutQuery(joinPpc: $joinPpc);
    $query = $this->applyDateOrWorkweekWipFilter($query, 'f3.date_loaded', $useWorkweek, $workweek, $startDate, $endDate);
    return $query;
  }

  public function overallQty(?string $joinPpc, bool $useWorkweek, $workweek, string $startDate, string $endDate): int
  {
    $query = $this->overallByDate($joinPpc,  $useWorkweek, $workweek, $startDate, $endDate);
    return $query->sum('f3.qty');
  }

  public function overallQtyAndLotIdByPackage(?string $joinPpc, bool $useWorkweek, $workweek, string $startDate, string $endDate)
  {
    $query = $this->overallByDate($joinPpc, $useWorkweek, $workweek, $startDate, $endDate);
    return $query->selectRaw('f3_pkg.package_name as package, SUM(f3.qty) as f3_total_out, SUM(f3.qty) as total_out, COUNT(DISTINCT f3.lot_number) as f3_total_lots, COUNT(DISTINCT f3.lot_number) as total_lots')
      ->groupBy('f3_pkg.package_name')
      ->get();
  }

  public function getOverallTrend($packageNames, $period, $startDate, $endDate, $workweeks, $aggregate = true)
  {
    $query = $this->baseF3OutQuery();

    $data = $this->analogCalendarRepo->getDatesByWorkWeekRange($workweeks);
    $weekRange = $this->shiftRangeByOneDayForward($data['range']);

    if ($aggregate) {
      $query = $this->applyTrendAggregation(
        $query,
        $period,
        $startDate,
        $endDate,
        'f3.date_loaded',
        ['SUM(f3.qty)' => 'total_outs'], // aggregate by outs
        workRange: $weekRange,
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

      // $this->shiftOneDayBack($results, $period);

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
