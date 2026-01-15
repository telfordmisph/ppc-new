<?php

namespace App\Services\PackageFilters;

use App\Constants\WipConstants;
use App\Repositories\PackageGroupRepository;
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

  protected function hasAny(array $a, array $b): bool
  {
    return !empty(array_intersect($a, $b));
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

  private function isSubset(array $subset, array $set): bool
  {
    return empty(array_diff($subset, $set));
  }

  protected function resolveStrategy(array $packageNames, $column, $factories, $trendType): PackageFilterStrategy
  {
    $packageNames = array_map('strtoupper', $packageNames);
    Log::info("resolveStrategy Package names: " . json_encode($packageNames));

    if (
      $this->hasAny($this->f1f2Out150Mils, $packageNames) &&
      (in_array("F1", $factories) || in_array("F2", $factories)) &&
      $trendType === "OUT"
    ) {
      return new DefaultPackageFilter(array_merge($packageNames, $this->f1f2Out150Mils), column: $column);
    }

    if (in_array("F3", $factories)) {
      return new F3PackageDimensionFilter($this->packageGroupRepo, $packageNames);
    }

    // if (array_map('strtolower', $packageNames) === $this->tssop240Mils && $factory === "F3") {
    //   return new Tssop240MilsPackageFilter();
    // }

    // f1 f2 f3
    // tssop 240, 150mils, lfcsp

    return new DefaultPackageFilter($packageNames, $column);
  }
}
