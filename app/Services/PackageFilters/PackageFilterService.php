<?php

namespace App\Services\PackageFilters;

use App\Constants\WipConstants;
use App\Repositories\PackageGroupRepository;
use App\Services\PackageFilters\Tssop240MilsSpecificPackageFilter;
use Illuminate\Support\Facades\Log;

class PackageFilterService
{
  protected PackageGroupRepository $packageGroupRepo;
  protected $tssop240Mils;
  protected $f1f2Out150Mils;

  public function __construct(PackageGroupRepository $packageGroupRepo)
  {
    $this->packageGroupRepo = $packageGroupRepo;
    $this->tssop240Mils = array_map('strtoupper', WipConstants::SPECIAL_FILTER_VALUE);
    $this->f1f2Out150Mils = array_map('strtoupper', WipConstants::F1F2_150_MILS_OUT_PACKAGE_VALUES);
  }

  protected function hasAtLeastOne(array $a, array $b): bool
  {
    return !empty(array_intersect($a, $b));
  }

  protected function arraysEqual(array $a, array $b): bool
  {
    sort($a);
    sort($b);

    return $a === $b;
  }

  protected function hasSpecificAndAtLeastOneOther(array $a, array $required): bool
  {
    // Check that all required items exist in $a
    if (count(array_diff($required, $a)) > 0) {
      return false;
    }

    // Check that there's at least one item in $a that's not in $required
    return count(array_diff($a, $required)) > 0;
  }

  public function applyPackageFilter($query, ?array $packageNames, $factories, $column, $trendType = null)
  {
    if (is_string($packageNames)) {
      $packageNames = explode(',', $packageNames);
    }
    $packageNames = array_filter((array) $packageNames, fn($p) => !empty($p));

    $strategy = $this->resolveStrategy($packageNames, $column, $factories, $trendType);
    return $strategy->apply($query);
  }

  protected function resolveStrategy(array $packageNames, $column, $factories, $trendType): PackageFilterStrategy
  {
    $packageNames = array_map('strtoupper', $packageNames);
    // Log::info("resolveStrategy Package names: " . json_encode($packageNames));

    if (($factories === ["F3"]) && $this->arraysEqual($this->tssop240Mils, $packageNames)) {
      // specific TSSOP (240 mils)
      return new Tssop240MilsSpecificPackageFilter();
    }

    if (($factories === ["F3"]) && $this->hasSpecificAndAtLeastOneOther($packageNames, $this->tssop240Mils)
    ) {
      return new Tssop240MilsAndOtherPackageFilter($packageNames);
    }

    if (
      $this->hasAtLeastOne($this->f1f2Out150Mils, $packageNames) &&
      (in_array("F1", $factories) || in_array("F2", $factories)) &&
      $trendType === "OUT"
    ) {
      return new DefaultPackageFilter(array_merge($packageNames, $this->f1f2Out150Mils), column: $column);
    }

    if (in_array("F3", $factories)) {
      return new DefaultPackageFilter($packageNames, 'f3_pkg.package_name');
    }

    return new DefaultPackageFilter($packageNames, $column);
  }
}
