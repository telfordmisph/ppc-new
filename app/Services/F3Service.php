<?php

namespace App\Services;

use App\Models\F3PackageName;
use App\Repositories\F3RawPackageRepository;

class F3Service
{
  protected F3RawPackageRepository $repo;

  public function __construct(F3RawPackageRepository $repo)
  {
    $this->repo = $repo;
  }

  public function createRawPackage(array $data)
  {
    if (!empty($data['package_id']) && !F3PackageName::find($data['package_id'])) {
      throw new \InvalidArgumentException('Invalid id of package: does not exist.');
    }

    return $this->repo->create($data);
  }
}
