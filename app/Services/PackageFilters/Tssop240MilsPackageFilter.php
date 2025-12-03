<?php

namespace App\Services\PackageFilters;

class Tssop240MilsPackageFilter implements PackageFilterStrategy
{
  public function apply($query)
  {
    $query->where('raw.dimension', ['240mils']);
    return $query;
  }
}
