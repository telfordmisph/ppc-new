<?php

namespace App\Repositories;

use App\Traits\PackageAliasTrait;
use App\Repositories\PackageGroupRepository;
use App\Repositories\AnalogCalendarRepository;
use App\Services\PackageFilters\PackageFilterService;
use App\Traits\TrendAggregationTrait;
use App\Traits\F3Trait;
use App\Models\F3Wip;
use App\Models\F1F2Out;
use App\Helpers\WipTrendParser;
use Illuminate\Support\Facades\DB;
use App\Helpers\SqlDebugHelper;
use Illuminate\Support\Facades\Log;

class F3WipRepository
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
    PackageGroupRepository $packageGroupRepo
  ) {
    $this->packageFilterService = $packageFilterService;
    $this->analogCalendarRepo = $analogCalendarRepo;
    $this->packageGroupRepo = $packageGroupRepo;
  }

  public function getTrend($packageName, $period, $startDate, $endDate, $workweeks)
  {
    $query = $this->baseF3Query();

    $query = $this->applyTrendAggregation(
      $query,
      $period,
      $startDate,
      $endDate,
      'f3_wip.date_loaded',
      ['SUM(f3_wip.qty)' => 'total_quantity'],
      workweeks: $workweeks
    );

    $query = $this->filterByPackageName($query, $packageName, 'f3_pkg.package_name');

    $results = DB::query()
      ->fromSub($query, 'combined')
      ->get();

    return $results;
  }

  public function insertCustomer(array $data)
  {
    F1F2Out::create($data);
  }

  public function insertManyF3(array $data)
  {
    F3Wip::insert($data);
  }
}
