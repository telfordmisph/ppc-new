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
  protected $table = "f3_wip";
  protected $tableAlias = "f3_wip";
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
      'f3_wip.date_received',
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



  //   -- using aliases
  // SELECT o.*
  // FROM customer_data_wip_out o
  // JOIN packages p ON o.package = p.package_name
  // JOIN package_group_members pgm ON p.id = pgm.package_id
  // JOIN package_group_members pgm_input ON pgm.group_id = pgm_input.group_id
  // JOIN packages p_input ON pgm_input.package_id = p_input.id
  // WHERE p_input.package_name = 'LFCSP';


  // WITH wip_packages AS (
  //     SELECT o.*, p.id AS package_id, p.package_name
  //     FROM f3_wip o
  //     JOIN f3_raw_packages f3r ON f3r.id = o.package
  //     JOIN f3_package_names f3p ON f3p.id = f3r.package_id
  //     JOIN packages p ON p.package_name = f3p.package_name
  // )
  // SELECT w.*
  // FROM wip_packages w
  // JOIN package_group_members pgm ON w.package_id = pgm.package_id
  // JOIN package_group_members pgm_input ON pgm.group_id = pgm_input.group_id
  // JOIN packages p_input ON pgm_input.package_id = p_input.id
  // WHERE p_input.package_name = 'QSOP';
