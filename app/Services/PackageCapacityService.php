<?php

namespace App\Services;

use App\Repositories\PackageCapacityRepository;

class PackageCapacityService
{
  protected $packageCapacityRepository;

  public function __construct(PackageCapacityRepository $packageCapacityRepository)
  {
    $this->packageCapacityRepository = $packageCapacityRepository;
  }

  public function getLatestPackageCapacity($package, $factory)
  {
    return $this->packageCapacityRepository->getLatestPackageCapacity($package, $factory);
  }

  public function insert($latest, $packageCapacity)
  {
    return $this->packageCapacityRepository->insert($latest, $packageCapacity);
  }

  public function update($latest, $id, $packageCapacity)
  {
    return $this->packageCapacityRepository->update($latest, $id, $packageCapacity);
  }

  public function get($id)
  {
    return $this->packageCapacityRepository->get($id);
  }

  public function rules($package_name, $factory_name, $effective_from, $id = null, $latest = null)
  {

    $uniqueRule = 'unique:package_capacity_history,package_name,' . ($id ?? 'NULL')
      . ',id,factory_name,' . $factory_name
      . ',effective_from,' . $effective_from;

    $rules = [
      'package_name'   => ['required', 'string', 'max:45', $uniqueRule],
      'factory_name'   => 'required|string|max:20',
      'capacity'       => 'nullable|integer|min:0',
      'effective_from' => ['required', 'date'],
      'effective_to'   => 'nullable|date|after_or_equal:effective_from',
    ];

    if ($latest) {
      $rules['effective_from'][] = function ($attribute, $value, $fail) use ($latest) {
        if ($value <= $latest->effective_from) {
          $fail("The effective_from date must be after the latest capacity date ({$latest->effective_from}).");
        }
      };
    }

    return $rules;
  }

  public function getSummaryLatestAndPrevious($factory)
  {
    return $this->packageCapacityRepository->getSummaryLatestAndPrevious($factory);
  }
}
