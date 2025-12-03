<?php

namespace App\Http\Controllers;

use App\Models\PackageCapacity;
use App\Repositories\AnalogCalendarRepository;
use App\Traits\ParseRequestTrait;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use App\Services\PackageCapacityService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PackageCapacityController extends Controller
{
  use ParseRequestTrait;
  protected $packageCapacityService;
  protected $analogCalendarRepo;

  public function __construct(
    PackageCapacityService $packageCapacityService,
    AnalogCalendarRepository $analogCalendarRepo
  ) {
    $this->packageCapacityService = $packageCapacityService;
    $this->analogCalendarRepo = $analogCalendarRepo;
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

  public function upload(Request $request)
  {
    return Inertia::render('PackageCapacityUpload');
  }

  public function getTrend(Request $request)
  {
    $packageName = $this->parsePackageName($request);
    $factory = $request->input('factory', 'F1');
    $workweekParams = $this->parseWorkweek($request);
    $periodParams = $this->parsePeriodParams($request);

    $test = $this->packageCapacityService->getCapacityTrend(
      $packageName,
      $factory,
      $periodParams['period'],
      $periodParams['startDate'],
      $periodParams['endDate'],
      $workweekParams['workweek'],
    );

    return response()->json($test);
  }
}
