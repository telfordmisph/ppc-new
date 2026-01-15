<?php

namespace App\Http\Controllers;

use App\Models\PackageGroup;
use App\Traits\ParseRequestTrait;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use App\Repositories\PackageGroupRepository;

class PackageGroupController extends Controller
{
  use ParseRequestTrait;
  protected $packageGroupRepo;

  public function __construct(PackageGroupRepository $packageGroupRepo)
  {
    $this->packageGroupRepo = $packageGroupRepo;
  }

  public function index(Request $request)
  {
    $perPage = $request->input('perPage', 50);
    $factory = $request->input('factory', 'F1');
    $totalEntries = PackageGroup::count();

    $packageGroups = PackageGroup::with('packages')
      ->when($factory, function ($query, $factory) {
        return $query->where('factory', $factory);
      })->get();

    return Inertia::render('PackageGroupList', [
      'packageGroups' => $packageGroups,
      'factory' => $factory,
    ]);
  }

  private function validatePackageGroup(Request $request, $id = null)
  {
    return $request->validate([
      'factory' => 'required|string|max:20',
      'group_name' => 'nullable|string|max:45',
      'package_names.*' => 'string|max:45',
      'package_names' => 'array|distinct',
    ]);
  }

  public function upsert($id = null)
  {
    $packageGroup = $id ? PackageGroup::with('packages')->findOrFail($id) : null;
    $packageMembers = $packageGroup ? $packageGroup->packages : [];

    return Inertia::render('PackageGroupUpsert', [
      'packageGroup' => $packageGroup,
      'packageMembers' => $packageMembers
    ]);
  }

  public function saveGroup(Request $request)
  {
    $validated = $this->validatePackageGroup($request);

    $id = $request->input('id');
    $factory = $request->input('factory');
    $groupName = $request->input('group_name') ?? null;
    $packageNames = $this->parsePackageName($request);

    $packageGroup = $this->packageGroupRepo->saveGroup($id, $factory, $groupName, $packageNames);
    return response()->json($packageGroup);
  }

  public function destroy($id)
  {
    $f3RawPackage = PackageGroup::findOrFail($id);
    $f3RawPackage->delete();

    return response()->json([
      'success' => true,
      'message' => 'Package deleted successfully',
    ]);
  }
}
