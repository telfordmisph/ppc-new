<?php

namespace App\Services\PackageFilters;

use Illuminate\Support\Facades\DB;
use App\Repositories\PackageGroupRepository;

class F3PackageDimensionFilter implements PackageFilterStrategy
{
  protected $package;
  protected PackageGroupRepository $packageGroupRepo;
  public function __construct(PackageGroupRepository $packageGroupRepo, $package)
  {
    $this->packageGroupRepo = $packageGroupRepo;
    $this->package = $package;
  }
  public function apply($query)
  {
    $groups = $this->packageGroupRepo->separateByGroups($this->package, ["F3"]);

    $allQueries = [];
    \Log::info("f3 filter packages: " . json_encode($this->package));
    \Log::info("f3 filter package groups: " . json_encode($groups));

    $base = (clone $query);

    if (!empty($groups)) {
      foreach ($groups as $groupId => $packagesInGroup) {
        // \Log::info("F3 Package Dimension Filter - Group ID: " . $groupId . ", Packages: " . json_encode($packagesInGroup));

        $queryByPackage = (clone $base)->whereIn('f3_pkg.package_name', $packagesInGroup);
        $queryByDimension = (clone $base)->whereIn('raw.dimension', $packagesInGroup);

        $allQueries[] = $queryByPackage;
        $allQueries[] = $queryByDimension;
      }
    }

    if (empty($groups) && !empty($this->package)) {
      $allQueries[] = (clone $query)->whereIn('f3_pkg.package_name', $this->package);
      $allQueries[] = (clone $query)->whereIn('raw.dimension', $this->package);
    }

    if (empty($allQueries)) {
      return $query;
    }

    $union = array_shift($allQueries);

    foreach ($allQueries as $q) {
      $union = $union->unionAll($q);
    }

    return $union;
  }
}
