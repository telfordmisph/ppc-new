<?php

namespace App\Repositories;

use App\Models\F3PackageName;

class F3PackageRepository
{
  /**
   * Create a new package.
   *
   * @param array $data
   * @return F3PackageName
   */
  public function create(array $data): F3PackageName
  {
    return F3PackageName::create([
      'package_name' => $data['package_name'] ?? null,
    ]);
  }

  /**
   * Get a package by ID.
   *
   * @param int $id
   * @return F3PackageName|null
   */
  public function find(int $id): ?F3PackageName
  {
    return F3PackageName::find($id);
  }

  /**
   * Update a package by ID.
   *
   * @param int $id
   * @param array $data
   * @return bool
   */
  public function update(int $id, array $data): bool
  {
    $package = $this->find($id);
    if (!$package) {
      return false;
    }

    return $package->update([
      'package_name' => $data['package_name'] ?? $package->package_name,
    ]);
  }

  /**
   * Delete a package by ID.
   *
   * @param int $id
   * @return bool|null
   */
  public function delete(int $id): ?bool
  {
    $package = $this->find($id);
    if (!$package) {
      return null;
    }

    return $package->delete();
  }

  /**
   * Get all packages.
   *
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public function all()
  {
    return F3PackageName::all();
  }
}
