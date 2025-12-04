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

    foreach ($groups as $groupId => $packagesInGroup) {
      \Log::info("F3 Package Dimension Filter - Group ID: " . $groupId . ", Packages: " . json_encode($packagesInGroup));

      $base = (clone $query);

      $queryByPackage = (clone $base)->whereIn('f3_pkg.package_name', $packagesInGroup);
      $queryByDimension = (clone $base)->whereIn('raw.dimension', $packagesInGroup);

      $allQueries[] = $queryByPackage;
      $allQueries[] = $queryByDimension;
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
