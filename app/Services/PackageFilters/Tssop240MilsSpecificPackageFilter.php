<?php

namespace App\Services\PackageFilters;

class Tssop240MilsSpecificPackageFilter implements PackageFilterStrategy
{
  public function apply($query)
  {
    return (clone $query)
      ->whereIn('f3_pkg.package_name', ['TSSOP'])
      ->where('raw.dimension', ['240mils']);
  }
}
