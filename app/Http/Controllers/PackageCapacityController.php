<?php

namespace App\Http\Controllers;

use App\Models\PackageCapacity;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use App\Services\PackageCapacityService;
use Illuminate\Http\Request;

class PackageCapacityController extends Controller
{
  protected $packageCapacityService;

  public function __construct(PackageCapacityService $packageCapacityService)
  {
    $this->packageCapacityService = $packageCapacityService;
  }

  public function storeCapacity(Request $request)
  {
    $latest = $this->packageCapacityService->getLatestPackageCapacity($request->package_name, $request->factory_name);
    $rules = $this->packageCapacityService->rules($request->package_name, $request->factory_name, $request->effective_from, null, $latest);
    $validated = $request->validate($rules);

    $result = $this->packageCapacityService->insert($latest, $validated);

    return response()->json([
      'message' => 'Package Capacity added successfully',
      'data' => $result,
    ]);
  }

  public function updateCapacity(Request $request, $id)
  {
    $current = $this->packageCapacityService->get($id);
    if (!$current) {
      abort(404, "Capacity record not found.");
    }

    $latest = $this->packageCapacityService->getLatestPackageCapacity($request->package_name, $request->factory_name);

    $rules = $this->packageCapacityService->rules($request->package_name, $request->factory_name, $request->effective_from, $id, $latest);
    $validated = $request->validate($rules);

    $result = $this->packageCapacityService->update($latest, $id, $validated);

    return response()->json([
      'message' => 'Package Capacity updated successfully',
      'data' => $result,
    ]);
  }

  public function getSummaryLatestAndPrevious(Request $request)
  {
    $factory = $request->input('factory', 'F1');

    return Inertia::render('PackageCapacityList', [
      'summary' => $this->packageCapacityService->getSummaryLatestAndPrevious($factory),
      'factory' => $factory,
    ]);
  }
}
