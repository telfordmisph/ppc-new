<?php

namespace App\Services\PackageFilters;

class Tssop240MilsAndOtherPackageFilter implements PackageFilterStrategy
{
  protected $package;
  public function __construct($package)
  {
    $this->package = $package;
  }

  public function apply($query)
  {
    return (clone $query)
      ->where(function ($q) {
        // First group: TSSOP + 240mils
        $q->where('f3_pkg.package_name', 'TSSOP')
          ->where('raw.dimension', '240mils');
      })
      ->orWhere(function ($q) {
        // Second group: other packages
        $q->whereIn('f3_pkg.package_name', $this->package)
          ->where('f3_pkg.package_name', '!=', 'TSSOP');
      });
  }
}
