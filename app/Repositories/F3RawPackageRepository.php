<?php

namespace App\Repositories;

use App\Models\F3RawPackage;
use App\Models\F3PackageName;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class F3RawPackageRepository
{
  /**
   * Create a new raw package record.
   *
   * @param array $data
   * @return F3RawPackage
   */
  public function create(array $data): F3RawPackage
  {
    $this->validatePackageId($data['package_id'] ?? null);

    return F3RawPackage::create([
      'raw_package' => $data['raw_package'] ?? null,
      'lead_count'  => $data['lead_count'] ?? null,
      'package_id'  => $data['package_id'] ?? null,
      'dimension'   => $data['dimension'] ?? null,
    ]);
  }

  /**
   * Update an existing raw package.
   *
   * @param int $id
   * @param array $data
   * @return bool
   */
  public function update(int $id, array $data): bool
  {
    $package = F3RawPackage::find($id);
    if (!$package) {
      throw new ModelNotFoundException("F3RawPackage not found.");
    }

    if (isset($data['package_id'])) {
      $this->validatePackageId($data['package_id']);
    }

    return $package->update($data);
  }

  /**
   * Delete a raw package.
   *
   * @param int $id
   * @return bool|null
   */
  public function delete(int $id): ?bool
  {
    $package = F3RawPackage::find($id);
    if (!$package) {
      return null;
    }

    return $package->delete();
  }

  /**
   * Get a raw package by ID.
   */
  public function find(int $id): ?F3RawPackage
  {
    return F3RawPackage::find($id);
  }

  /**
   * Get all raw packages.
   */
  public function all()
  {
    return F3RawPackage::all();
  }

  /**
   * Validate that package_id exists in F3Package table.
   */
  protected function validatePackageId(?int $packageId)
  {
    if ($packageId && !F3PackageName::find($packageId)) {
      throw new \InvalidArgumentException("Invalid package_id: $packageId does not exist.");
    }
  }

  public function getIDByRawPackage(?string $rawPackage): ?int
  {
    if ($rawPackage === null) {
      return null;
    }
    return F3RawPackage::where('raw_package', $rawPackage)->value('id');
  }
}
