<?php

namespace App\Repositories;

use App\Constants\WipConstants;
use App\Traits\TrendAggregation;
use App\Models\F3Wip;
use App\Models\F1F2Out;
use Illuminate\Support\Facades\DB;
use App\Helpers\WipTrendParser;

class F3WipRepository
{
  use TrendAggregation;

  public const F3_WIP_TABLE = "f3_wip";
  public const PACKAGES_TABLE = "f3_package_names";
  public const RAW_PACKAGES = "f3_raw_packages";
  private const PPC_TABLE = "ppc_productionline_packagereference";

  public function getExistingRecords()
  {
    return DB::table(self::F3_WIP_TABLE)
      ->select('lot_number', 'date_received')
      ->where('date_received', '>=', now()->subDays(28))
      ->get();
  }

  public function filterByPackageName($query, ?array $packageNames)
  {
    if (is_string($packageNames)) {
      $packageNames = explode(',', $packageNames);
    }
    $packageNames = array_filter((array) $packageNames, fn($p) => !empty($p));

    if (!empty($packageNames)) {
      $query->whereIn('f3_pkg.package_name', $packageNames);
    }

    return $query;
  }

  public function baseF3Query($joinPpc = false)
  {
    $query = DB::table(self::F3_WIP_TABLE . ' as f3_wip');
    $query->leftJoin(self::RAW_PACKAGES . ' as raw', 'f3_wip.package', '=', 'raw.id');
    $query->leftJoin(self::PACKAGES_TABLE . ' as f3_pkg', 'raw.package_id', '=', 'f3_pkg.id');

    if ($joinPpc) {
      $query->join(self::PPC_TABLE . ' as plref', 'f3_pkg.package_name', '=', 'plref.Package');
    }

    return $query;
  }

  public function getOverallTrend($packageName, $period, $lookBack, $offsetDays, $workweeks)
  {
    $query = $this->baseF3Query();
    $query = $this->filterByPackageName($query, $packageName);
    $query = $query->orderByDesc('outs_total_quantity');

    $query = $this->applyTrendAggregation(
      $query,
      $period,
      $lookBack,
      $offsetDays,
      'f3_wip.date_received',
      ['SUM(f3_wip.qty)' => 'outs_total_quantity'],
      workweeks: $workweeks
    )->get();

    $trends['overall_trend'] = $query;

    return WipTrendParser::parseTrendsByPeriod($trends);
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
