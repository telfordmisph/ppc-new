<?php

namespace App\Repositories;

use App\Models\CustomerDataWip;
use App\Models\F3DataWip;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use App\Constants\WipConstants;

class WipRepository
{
  private const F1F2_TABLE = "customer_data_wip";
  private const F3_TABLE = "f3_data_wip";
  private const PPC_TABLE = "ppc_productionline_packagereference";

  public function getExistingRecords()
  {
    return DB::table('customer_data_wip')
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
}
