<?php

namespace App\Repositories;

use App\Constants\WipConstants;
use App\Traits\TrendAggregation;

use App\Models\F3Out;
use Illuminate\Support\Facades\DB;
use App\Helpers\WipTrendParser;

class F3OutRepository
{
  use TrendAggregation;

  public const OUTS_TABLE = "f3_out";
  protected $analogCalendarRepo;
  public const PACKAGES_TABLE = "f3_package_names";
  public const RAW_PACKAGES = "f3_raw_packages";
  private const PPC_TABLE = "ppc_productionline_packagereference";

  public function __construct(
    AnalogCalendarRepository $analogCalendarRepo,
  ) {
    $this->analogCalendarRepo = $analogCalendarRepo;
  }

  public function getExistingRecords()
  {
    return DB::table(self::OUTS_TABLE)
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

    \Log::info("packagenames: " . json_encode($packageNames));

    if (!empty($packageNames)) {
      $query->whereIn('f3_pkg.package_name', $packageNames);
    }

    return $query;
  }

  public function baseF3Query($joinPpc = false)
  {
    $query = DB::table(self::OUTS_TABLE . ' as f3_out');
    $query->leftJoin(self::RAW_PACKAGES . ' as raw', 'f3_out.package', '=', 'raw.id');
    $query->leftJoin(self::PACKAGES_TABLE . ' as f3_pkg', 'raw.package_id', '=', 'f3_pkg.id');

    if ($joinPpc) {
      $query->join(self::PPC_TABLE . ' as plref', 'f3_pkg.package_name', '=', 'plref.Package');
    }

    return $query;
  }

  public function getOverallTrend($packageNames, $period, $lookBack, $offsetDays, $workweeks)
  {
    $query = $this->baseF3Query();
    $query = $this->filterByPackageName($query, $packageNames);
    $query = $query->orderByDesc('total_outs');

    $query = $this->applyTrendAggregation(
      $query,
      $period,
      $lookBack,
      $offsetDays,
      'f3_out.date_received',
      ['SUM(f3_out.qty)' => 'total_outs'],
      workweeks: $workweeks
    );

    $sql = $query->toSql();
    \Log::info($sql);

    $query = $query->get();

    $trends['overall_trend'] = $query;

    return WipTrendParser::parseTrendsByPeriod($trends);
  }

  public function insertManyCustomer(array $data)
  {
    F3Out::insert($data);
  }
}
