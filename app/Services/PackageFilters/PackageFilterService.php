<?php

namespace App\Services\PackageFilters;

use App\Constants\WipConstants;
use App\Repositories\PackageGroupRepository;
use Illuminate\Support\Facades\Log;

class PackageFilterService
{
  protected PackageGroupRepository $packageGroupRepo;
  protected $tssop240Mils;
  protected $f1f2OutPackages;

  public function __construct(PackageGroupRepository $packageGroupRepo)
  {
    $this->packageGroupRepo = $packageGroupRepo;
    $this->tssop240Mils = array_map('strtoupper', WipConstants::SPECIAL_FILTER_VALUE);
    $this->f1f2OutPackages = array_map('strtoupper', WipConstants::F1F2_OUT_PACKAGE_VALUES);
  }

  public function apply($query, ?array $packageNames, $factory, $column, $trendType = null)
  {
    if (is_string($packageNames)) {
      $packageNames = explode(',', $packageNames);
    }
    $packageNames = array_filter((array) $packageNames, fn($p) => !empty($p));

    Log::info("Applying package filter with names: " . json_encode($packageNames) . ", factory: " . $factory . ", column: " . $column . ", trendType: " . $trendType);

    $strategy = $this->resolveStrategy($packageNames, $column, $factory, $trendType);
    return $strategy->apply($query);
  }

  private function isSubset(array $subset, array $set): bool
  {
    return empty(array_diff($subset, $set));
  }

  protected function resolveStrategy(array $packageNames, $column, $factory, $trendType): PackageFilterStrategy
  {
    $packageNames = array_map('strtoupper', $packageNames);

    if (array_map('strtolower', $packageNames) === $this->tssop240Mils && $factory === "F3") {
      return new Tssop240MilsPackageFilter();
    }

    if ($this->isSubset($packageNames, $this->f1f2OutPackages) && ($factory === "F1" || $factory === "F2") && $trendType === "OUT") {
      return new DefaultPackageFilter(["150mils"], column: $column);
    }

    // f1 f2 f3
    // tssop 240, 150mils, lfcsp

    return new DefaultPackageFilter($packageNames, $column);
  }
}
