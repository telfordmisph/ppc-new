<?php

namespace App\Repositories;

use App\Models\CustomerDataWip;
use App\Models\F3Wip;
use App\Traits\PackageAliasTrait;
use App\Traits\TrendAggregationTrait;
use Illuminate\Database\Query\Builder;
use App\Repositories\PackageGroupRepository;
use App\Repositories\AnalogCalendarRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Constants\WipConstants;
use App\Services\PackageFilters\PackageFilterService;
use Illuminate\Support\Facades\Log;
use App\Helpers\SqlDebugHelper;

class F1F2WipRepository
{
  use PackageAliasTrait;
  use TrendAggregationTrait;

  private const F1F2_TABLE = "customer_data_wip";
  private const DISTINCT_PACKAGE_CACHE_KEY = 'distinct_packages';
  private const CACHE_HOURS = 26;
  private const F3_TABLE = "f3_data_wip";
  private const PPC_TABLE = "ppc_productionline_packagereference";
  protected $packageFilterService;
  protected $packageGroupRepo;
  protected $analogCalendarRepo;

  public function __construct(
    PackageFilterService $packageFilterService,
    AnalogCalendarRepository $analogCalendarRepo,
    PackageGroupRepository $packageGroupRepo
  ) {
    $this->packageFilterService = $packageFilterService;
    $this->analogCalendarRepo = $analogCalendarRepo;
    $this->packageGroupRepo = $packageGroupRepo;
  }

  private const KEYS = [
    'Plant',
    'Part_Name',
    'Lead_Count',
    'Package_Name',
    'Lot_Id',
    'Station',
    'Qty',
    'Lot_Type',
    'Prod_Area',
    'Lot_Status',
    'Date_Loaded',
    'Start_Time',
    'Part_Type',
    'Part_Class',
    'Date_Code',
    'Focus_Group',
    'Process_Group',
    'Bulk',
    'Reqd_Time',
    'Lot_Entry_Time',
    'Stage',
    'Stage_Start_Time',
    'CCD',
    'Stage_Run_Days',
    'Lot_Entry_Time_Days',
    'Tray',
    'Backend_Leadtime',
    'OSL_Days',
    'BE_Group',
    'Strategy_Code',
    'CR3',
    'BE_Starttime',
    'BE_OSL_Days',
    'Body_Size',
    'Auto_Part',
    'Ramp_Time',
    'End_Customer',
    'Bake',
    'Bake_Count',
    'Test_Lot_Id',
    'Stock_Position',
    'Assy_Site',
    'Bake_Time_Temp',
    'imported_by'
  ];

  public static function getKeys(): array
  {
    return self::KEYS;
  }

  public function getExistingRecords()
  {
    return DB::table(self::F1F2_TABLE)
      ->select('Lot_Id', 'Date_Loaded', DB::raw('DATE(Date_Loaded) as dateonly'), 'Focus_Group')
      ->where('Date_Loaded', '>=', now()->subDays(WipConstants::DAYS_UNTIL_RECLASSIFIED_AS_NEW))
      ->unionAll(
        DB::table('f3_data_wip')
          ->select('Lot_Id', 'Date_Loaded', DB::raw('DATE(Date_Loaded) as dateonly'), 'Focus_Group')
          ->where('Date_Loaded', '>=', now()->subDays(WipConstants::DAYS_UNTIL_RECLASSIFIED_AS_NEW))
      )
      ->get();
  }

  public function insertCustomer(array $data)
  {
    CustomerDataWip::create($data);
  }

  public function insertF3(array $data)
  {
    F3Wip::create($data);
  }

  public function insertManyCustomers(array $data)
  {
    CustomerDataWip::insert($data);
  }

  public function applyStationFilter($query, array $includeStations = [], array $excludeStations = []): Builder
  {
    $query->where(function ($q) use ($includeStations, $excludeStations) {
      $q->whereIn('wip.Station', $includeStations)
        ->orWhere(function ($q2) use ($excludeStations) {
          $q2->where('wip.station_suffix', '=', '_T')
            ->whereNotIn('wip.Station', $excludeStations);
        });
    });

    return $query;
  }

  public function baseF3Query($joinPpc = false): Builder
  {
    $query = DB::table(self::F3_TABLE . ' as wip')
      ->where('wip.Focus_Group', 'F3');

    if ($joinPpc) {
      $query->join(self::PPC_TABLE . ' as plref', 'wip.Package_Name', '=', 'plref.Package'); // TODO: TO BE CHANGED
    }

    return $query;
  }

  public function joinPL($query, $partName = [], $joinPpc = ''): Builder
  {
    $query->join(self::PPC_TABLE . ' as plref', 'wip.Package_Name', '=', 'plref.Package');

    if ($joinPpc == 'PL1') {
      $query->where(function ($q) use ($partName) {
        $q->Where('plref.production_line', 'PL1')
          ->orwhereIn('wip.Part_Name', $partName);
      });
    }

    if ($joinPpc == 'PL6') {
      $query->where(function ($q) use ($partName) {
        $q->where('plref.production_line', 'PL6')
          ->whereNotIn('wip.Part_Name', $partName);
      });
    }

    return $query;
  }

  public static function applyF1Filters($query, $excludedPlant, ?string $alias)
  {
    $prefix = $alias ? "{$alias}." : '';

    return $query
      ->where("{$prefix}f1_focus_group_flag", true)
      ->where("{$prefix}Plant", '!=', $excludedPlant);
  }

  public function applyF2Filters($query, array $excludedStation = [], ?string $alias = null)
  {
    $prefix = $alias ? "{$alias}." : '';

    $query->where("{$prefix}f2_focus_group_flag", true);

    if (!empty($excludedStation)) {
      $query->whereNotIn("{$prefix}Station", $excludedStation);
    }

    return $query;
  }

  public function f1Filters($query, $includedStations = [], $excludedStations = [], ?string $alias = null)
  {
    $query = $this->applyF1Filters($query, WipConstants::F1_EXCLUDED_PLANT, $alias);
    $query = $this->applyStationFilter($query, $includedStations, $excludedStations);
    return $query;
  }

  public function filterByPackageName($query, ?array $packageNames, $factory): Builder
  {
    $query = $this->packageFilterService->apply($query, $packageNames, null, 'wip.Package_Name');
    Log::info("filter query: " . SqlDebugHelper::prettify($query->toSql(), $query->getBindings()));
    // if (is_string($packageNames)) {
    //   $packageNames = explode(',', $packageNames);
    // }
    // $packageNames = array_filter((array) $packageNames, fn($p) => !empty($p));
    // $aliases = $this->packageGroupRepo->getMembersByPackageName($packageNames, $factory);
    // Log::info("Aliases f1 f2 wip: " . json_encode($aliases));
    // if (!empty($aliases)) {
    //   $query->whereIn('wip.Package_Name', $aliases);
    // }

    return $query;
  }

  public function getTrend(
    $factory,
    $packageName,
    $period,
    $startDate,
    $endDate,
    $workweeks = "",
    $selectColumns = ['wip.Date_Loaded as date_loaded', 'wip.Qty as qty', 'wip.Lot_Id as lot_id', 'wip.Package_Name as package_name'],
    $aggregateColumns = null
  ) {
    $selectColumns = !empty($columns) ? implode(', ', $selectColumns) : "1";

    if ($aggregateColumns === null) {
      $aggregateColumns = WipConstants::FACTORY_AGGREGATES[$factory]['wip']['quantity-lot'];
    }

    switch ($factory) {
      case 'F1F2':
        $f1f2 = DB::table(self::F1F2_TABLE . ' as wip')
          ->selectRaw($selectColumns)
          ->where(function ($sub) {
            // $sub->whereRaw('0 = 1');

            $sub->orWhere(fn($q) => $this->f1Filters(
              $q,
              WipConstants::REEL_TRANSFER_B3,
              WipConstants::REEL_TRANSFER_EXCLUDED_STATIONS_F1,
              'wip'
            ));

            $sub->orWhere(fn($q) => $this->applyF2Filters(
              $q,
              WipConstants::EWAN_PROCESS,
              'wip'
            ));
          });
        $query = $this->filterByPackageName($f1f2, $packageName, $factory);
        break;

      case 'F1':
        $query = DB::table(self::F1F2_TABLE . ' as wip')
          ->selectRaw($selectColumns);
        $query = $this->f1Filters(
          $query,
          WipConstants::REEL_TRANSFER_B3,
          WipConstants::REEL_TRANSFER_EXCLUDED_STATIONS_F1,
          'wip'
        );
        $query = $this->filterByPackageName($query, $packageName, $factory);
        break;

      case 'F2':
        $query = DB::table(self::F1F2_TABLE . ' as wip')
          ->selectRaw($selectColumns);
        $query = $this->applyF2Filters($query, WipConstants::EWAN_PROCESS, 'wip');
        $query = $this->filterByPackageName($query, $packageName, $factory);
        break;

      // case 'PL1':
      // case 'PL6':
      //   $query = DB::table(self::F1F2_TABLE . ' as wip')
      //     ->selectRaw($selectColumns);
      //   $query = $this->f1f2WipRepo->joinPL($query, WipConstants::SPECIAL_PART_NAMES, $factory);
      //   $query->where(function ($sub) {
      //     $this->f1f2WipRepo->f1Filters(
      //       $sub,
      //       WipConstants::REEL_TRANSFER_B3,
      //       WipConstants::REEL_TRANSFER_EXCLUDED_STATIONS_F1,
      //       'wip'
      //     );
      //     $sub->orWhere(fn($f2) => $this->f1f2WipRepo->applyF2Filters($f2, WipConstants::EWAN_PROCESS, 'wip'));
      //   });
      //   $query = $this->f1f2WipRepo->filterByPackageName($query, $packageName, $factory);
      //   break;

      // case 'F3':
      //   $query = $this->f3WipRepo->baseF3Query()
      //     ->selectRaw($selectColumns);
      //   $query = $this->f3WipRepo->filterByPackageName($query, $packageName, $factory);
      //   break;

      default:
        throw new \InvalidArgumentException("Unknown factory: $factory");
    }


    return $this->applyTrendAggregation(
      $query,
      $period,
      $startDate,
      $endDate,
      column: WipConstants::FACTORY_AGGREGATES[$factory]['wip']['dateColumn'],
      aggregateColumns: $aggregateColumns,
      workweeks: $workweeks,
    );
  }

  public function refreshDistinctPackagesCache(): void
  {
    Cache::forget(self::DISTINCT_PACKAGE_CACHE_KEY);

    $packages = DB::table(self::F1F2_TABLE)
      ->select('Package_Name')
      ->distinct()
      ->pluck('Package_Name');

    Cache::put(self::DISTINCT_PACKAGE_CACHE_KEY, $packages, now()->addHours(value: self::CACHE_HOURS));
  }

  public function getDistinctPackages()
  {
    return Cache::remember(self::DISTINCT_PACKAGE_CACHE_KEY, now()->addHours(self::CACHE_HOURS), function () {
      return DB::table(self::F1F2_TABLE)
        ->whereNotNull('Package_Name')
        ->where('Package_Name', '!=', '')
        ->distinct()
        ->orderBy('Package_Name')
        ->pluck('Package_Name')
        ->toArray();
    });
  }
}
