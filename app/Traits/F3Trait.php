<?php


namespace App\Traits;

use Illuminate\Support\Facades\DB;
use App\Constants\WipConstants;
use App\Traits\NormalizeStringTrait;
use Illuminate\Support\Facades\Log;

trait F3Trait
{
  use NormalizeStringTrait;
  public const PACKAGES_TABLE = "f3_package_names";
  public const RAW_PACKAGES = "f3_raw_packages";
  private const PPC_TABLE = "ppc_productionline_packagereference";
  public function getExistingRecords()
  {
    if (!isset($this->table)) {
      throw new \LogicException('The $table property must be defined in the class using this trait.');
    }

    return DB::table($this->table)
      ->select('lot_number', 'date_received')
      ->where('date_received', '>=', now()->subDays(WipConstants::DAYS_UNTIL_RECLASSIFIED_AS_NEW))
      ->get();
  }
  public function filterByPackageName($query, ?array $packageNames, $column = 'f3_pkg.package_name')
  {
    $query = $this->packageFilterService->apply($query, $packageNames, "F3", $column);

    // if (is_string($packageNames)) {
    //   $packageNames = explode(',', $packageNames);
    // }
    // $packageNames = array_filter((array) $packageNames, fn($p) => !empty($p));
    // $aliases = $this->packageGroupRepo->getMembersByPackageName($packageNames, "F3");

    // Log::info("Aliases f3: " . json_encode($aliases));

    // if (!empty($aliases)) {
    //   $query->whereIn($column, $aliases);
    //   // $this->applyNormalizedDimensionFilter($query, $aliases->toArray(), 'raw.dimension');
    //   // ->orWhereIn('raw.dimension', $aliases);

    //   // ->orWhereIn('raw.dimension', $aliases);
    // }

    return $query;
  }

  public function baseF3Query($joinPpc = false)
  {
    $query = DB::table($this->table . ' as ' . $this->tableAlias);
    $query->leftJoin(self::RAW_PACKAGES . ' as raw', $this->tableAlias . '.package', '=', 'raw.id');
    $query->leftJoin(self::PACKAGES_TABLE . ' as f3_pkg', 'raw.package_id', '=', 'f3_pkg.id');

    if ($joinPpc) {
      $query->join(self::PPC_TABLE . ' as plref', 'f3_pkg.package_name', '=', 'plref.Package');
    }

    return $query;
  }
}
