<?php

namespace App\Repositories;

use App\Models\CustomerDataWip;
use App\Models\F3DataWip;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Constants\WipConstants;

class WipRepository
{
  // TODO: either use wip alias consistently or not use it at all

  private const F1F2_TABLE = "customer_data_wip";
  private const DISTINCT_PACKAGE_CACHE_KEY = 'distinct_packages';
  private const CACHE_HOURS = 26;
  private const F3_TABLE = "f3_data_wip";
  private const PPC_TABLE = "ppc_productionline_packagereference";

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
    F3DataWip::create($data);
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
      $query->join(self::PPC_TABLE . ' as plref', 'wip.Package_Name', '=', 'plref.Package');
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

  public function filterByPackageName($query, ?string $packageNames): Builder
  {
    if (!$packageNames) return $query;
    return $query->where('wip.Package_Name', $packageNames);
  }

  /**
   * Get trend data aggregated by period (weekly, monthly, quarterly, yearly)
   *
   * @param string $period         'weekly' | 'monthly' | 'quarterly' | 'yearly'
   * @param int    $lookBack       How many periods to look back (weeks, months, etc.)
   * @param int    $offsetDays     Optional offset from today
   * @param string $column         Column to aggregate (default 'Date_Loaded')
   * @param string $quantityColumn Column to sum (default 'Qty')
   *
   * @return \Illuminate\Database\Query\Builder
   */
  public function getTrend(
    Builder $query,
    string $period = 'weekly',
    int $lookBack = 3,
    int $offsetDays = 0,
    string $column = 'Date_Loaded',
    string $quantityColumn = 'Qty'
  ) {
    $now = Carbon::now()->subDays($offsetDays)->endOfDay();

    // TODO: covers the current date even partial???

    // Determine start date based on period
    switch (strtolower($period)) {
      case 'daily':
        $startDate = (clone $now)->subDays($lookBack)->startOfDay();
        break;
      case 'weekly':
        $startDate = (clone $now)->subWeeks($lookBack)->startOfWeek();
        break;
      case 'monthly':
        $startDate = (clone $now)->subMonths($lookBack)->startOfMonth();
        break;
      case 'quarterly':
        $startDate = (clone $now)->subMonths($lookBack * 3)->startOfQuarter();
        break;
      case 'yearly':
        $startDate = (clone $now)->subYears($lookBack)->startOfYear();
        break;
      default:
        throw new \InvalidArgumentException("Invalid period: {$period}");
    }

    $query->whereBetween($column, [$startDate, $now]);

    // Apply grouping and aggregation
    switch (strtolower($period)) {
      case 'daily':
        $query->selectRaw("DATE({$column}) as day, SUM({$quantityColumn}) as total_quantity, COUNT(DISTINCT Lot_Id) AS total_lots")
          ->groupBy('day')
          ->orderBy('day');
        break;
      case 'weekly':
        $query->selectRaw("YEAR({$column}) as year, WEEK({$column}, 1) as week, SUM({$quantityColumn}) as total_quantity, COUNT(DISTINCT Lot_Id) AS total_lots")
          ->groupBy('year', 'week')
          ->orderBy('year')
          ->orderBy('week');
        break;
      case 'monthly':
        $query->selectRaw("YEAR({$column}) as year, MONTH({$column}) as month, SUM({$quantityColumn}) as total_quantity, COUNT(DISTINCT Lot_Id) AS total_lots")
          ->groupBy('year', 'month')
          ->orderBy('year')
          ->orderBy('month');
        break;
      case 'quarterly':
        $query->selectRaw("YEAR({$column}) as year, QUARTER({$column}) as quarter, SUM({$quantityColumn}) as total_quantity, COUNT(DISTINCT Lot_Id) AS total_lots")
          ->groupBy('year', 'quarter')
          ->orderBy('year')
          ->orderBy('quarter');
        break;
      case 'yearly':
        $query->selectRaw("YEAR({$column}) as year, SUM({$quantityColumn}) as total_quantity, COUNT(DISTINCT Lot_Id) AS total_lots")
          ->groupBy('year')
          ->orderBy('year');
        break;
    }

    return $query;
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
        ->pluck('Package_Name');
    });
  }
}
