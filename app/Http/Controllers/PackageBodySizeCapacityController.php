<?php

namespace App\Http\Controllers;

use App\Models\BodySizeCapacityProfile;
use App\Models\BodySize;
use App\Models\Machine;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Services\BodySizeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PackageBodySizeCapacityController extends Controller
{
  protected BodySizeService $bodySizeService;

  public function __construct(BodySizeService $bodySizeService)
  {
    $this->bodySizeService = $bodySizeService;
  }

  protected function rules(): array
  {
    return [
      'rows' => 'required|array',
      'rows.*.capacity_profile_id' => 'required|integer|exists:capacity_profiles,id',
      'rows.*.capacity' => 'required|numeric|min:0',
      'rows.*.factory' => 'nullable|string|max:50',
      'rows.*.machines' => 'nullable|array',
      'rows.*.machines.*' => 'integer|exists:machines,id',
    ];
  }

  public function bulkUpsert(Request $request)
  {
    $rows = $request->input('rows');
    $expireProfiles = $request->input('expire_profiles');

    $errors = [];
    $updated = [];
    $created = [];

    DB::transaction(function () use ($rows, $expireProfiles, &$errors, &$created) {
      foreach ($expireProfiles as $expireProfile) {
        $activeProfile = BodySizeCapacityProfile::where('id', $expireProfile)
          ->whereNull('effective_to')
          ->first();

        if (!$activeProfile) {
          continue;
        }

        $activeProfile->update([
          'effective_to' => now(),
        ]);
      }

      foreach ($rows as $row) {
        $validator = Validator::make($row, [
          'body_size_id' => 'required|integer|exists:body_sizes,id',
          'capacity' => 'required|numeric|min:0',
          'factory' => 'nullable|string|max:50',
          'machine_id' => 'nullable|integer|exists:machines,id',
        ]);

        if ($validator->fails()) {
          $errors[] = $validator->errors()->messages();
          continue;
        }

        $newProfile = BodySizeCapacityProfile::create([
          'body_size_id'  => $row['body_size_id'],
          'capacity'      => $row['capacity'],
          'factory'       => $row['factory'] ?? null,
          'machine_id'    => $row['machine_id'] ?? null,
          'effective_from' => now(),
          'effective_to'  => null,
        ]);

        $created[] = $newProfile->load('bodySize', 'machine');
      }
    });

    return response()->json([
      'status' => $errors ? 'error' : 'success',
      'updated' => $updated,
      'created' => $created,
      'errors' => $errors,
    ]);
  }

  public function index(Request $request)
  {
    $profiles = BodySizeCapacityProfile::with(['bodySize', 'machine'])
      ->whereNull('effective_to')      // current profiles only
      ->orderBy('body_size_id')
      ->orderByDesc('effective_from')  // latest first if multiple per body size
      ->get();

    $machines = Machine::with(['capacityProfiles.bodySize'])
      ->orderBy('name')
      ->get();

    $bodySizes = $this->bodySizeService->getBodySizeWipAndLot($request);

    return Inertia::render('PackageBodySizeCapacityList', [
      'profiles' => $profiles,
      'bodySizes' => $bodySizes,
      'machines' => $machines
    ]);
  }

  public function bodySizes(Request $request)
  {
    return Inertia::render('PackageBodySizeCapacityBodySizeList', []);
  }

  public function machines(Request $request)
  {
    return Inertia::render('PackageBodySizeCapacityMachineList', []);
  }
}
