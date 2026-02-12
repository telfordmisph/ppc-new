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
use App\Repositories\F1F2WipRepository;
use App\Repositories\F3WipRepository;
use App\Models\CustomerDataWip;
use App\Constants\WipConstants;

class BodySizeRepository
{
  protected $f1f2WipRepo;
  protected $f3WipRepo;

  public function __construct(F1F2WipRepository $f1f2WipRepo, F3WipRepository $f3WipRepo)
  {
    $this->f1f2WipRepo = $f1f2WipRepo;
    $this->f3WipRepo = $f3WipRepo;
  }

  public function getF1BodySizeWipAndLot($bodySizes, $startDate, $endDate)
  {
    $f1Query = DB::table((new CustomerDataWip)->getTable() . ' as wip')
      ->selectRaw('
          SUM(wip.Qty) AS total_wip,
          wip.canonical_body_size AS body_size,
          COUNT(wip.lot_id) AS lot_id
      ');

    $f1Query = $this->f1f2WipRepo->f1Filters($f1Query, ['GTREEL'], WipConstants::REEL_EXCLUDED_STATIONS_F1_BODY_SIZE_CAPACITY, 'wip');
    $f1Query =
      $f1Query
      ->whereIn('wip.canonical_body_size', $bodySizes)
      ->whereBetween('wip.Date_Loaded', [$startDate, $endDate])
      ->groupBy('wip.canonical_body_size')
      ->get();

    return $f1Query;
  }

  public function getF2BodySizeWipAndLot($bodySizes, $startDate, $endDate)
  {
    $f2Query = DB::table((new CustomerDataWip)->getTable() . ' as wip')
      ->selectRaw('
          SUM(wip.Qty) AS total_wip,
          wip.canonical_body_size AS body_size,
          COUNT(wip.lot_id) AS lot_id
      ');

    $f2Query = $this->f1f2WipRepo->applyF2Filters($f2Query, WipConstants::EWAN_PROCESS, 'wip');
    $f2Query =
      $f2Query
      ->whereIn('wip.canonical_body_size', $bodySizes)
      ->whereBetween('wip.Date_Loaded', [$startDate, $endDate])
      ->groupBy('wip.canonical_body_size')
      ->get();

    return $f2Query;
  }

  public function getF3BodySizeWipAndLot($bodySizes, $startDate, $endDate, $machineNumber)
  {
    $f3Query = $this->f3WipRepo->baseF3Query();
    $f3Query = $f3Query->selectRaw('
          SUM(f3.Qty) AS total_wip,
          raw.canonical_body_size AS body_size,
          COUNT(f3.lot_number) AS lot_id
      ');

    $f3Query =
      $f3Query
      ->where('f3.machine_number', $machineNumber)
      ->whereIn('raw.canonical_body_size', $bodySizes)
      ->whereBetween('f3.Date_Loaded', [$startDate, $endDate])
      ->groupBy('raw.canonical_body_size')
      ->get();

    return $f3Query;
  }
}
