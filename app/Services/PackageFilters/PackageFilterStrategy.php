<?php
// app/Services/PackageFilters/PackageFilterStrategy.php
namespace App\Services\PackageFilters;

interface PackageFilterStrategy
{
  public function apply($query);
}
