<?php

namespace App\Repositories;

use App\Models\CustomerDataWip;
use App\Models\F3Wip;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Constants\WipConstants;
use Log;

class F1F2WipRepository
{
  private const F1F2_TABLE = "customer_data_wip";
  private const DISTINCT_PACKAGE_CACHE_KEY = 'distinct_packages';
  private const CACHE_HOURS = 26;
  private const F3_TABLE = "f3_data_wip";
  private const PPC_TABLE = "ppc_productionline_packagereference";

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
      ->where('Date_Loaded', '>=', now()->subDays(7))
      ->unionAll(
        DB::table('f3_data_wip')
          ->select('Lot_Id', 'Date_Loaded', DB::raw('DATE(Date_Loaded) as dateonly'), 'Focus_Group')
          ->where('Date_Loaded', '>=', now()->subDays(7))
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

  public function filterByPackageName($query, ?array $packageNames): Builder
  {
    if (is_string($packageNames)) {
      $packageNames = explode(',', $packageNames);
    }
    $packageNames = array_filter((array) $packageNames, fn($p) => !empty($p));

    if (empty($packageNames)) return $query;

    return $query->whereIn('wip.Package_Name', $packageNames);
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
    // sleep(5);

    return Cache::remember(self::DISTINCT_PACKAGE_CACHE_KEY, now()->addHours(self::CACHE_HOURS), function () {
      // sleep(5);

      return DB::table(self::F1F2_TABLE)
        ->whereNotNull('Package_Name')
        ->where('Package_Name', '!=', '')
        ->distinct()
        ->orderBy('Package_Name')
        ->pluck('Package_Name');
    });
  }
}
