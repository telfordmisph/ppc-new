<?php

namespace App\Services\PackageFilters;

class DefaultPackageFilter implements PackageFilterStrategy
{
  protected $package;
  protected $column;

  public function __construct($package, $column)
  {
    $this->package = $package;
    $this->column = $column;
  }

  public function apply($query)
  {
    if (!empty($this->package)) {
      $query->whereIn($this->column, $this->package);
    }
    return $query;
  }
}
