<?php

namespace App\Repositories;

use App\Models\F3PackageName;

class F3PackageNamesRepository
{
  public function getAllPackageNames()
  {
    return F3PackageName::pluck('package_name')->toArray();
  }
}
