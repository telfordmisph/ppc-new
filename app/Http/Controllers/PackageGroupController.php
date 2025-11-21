<?php

namespace App\Http\Controllers;

use App\Models\PackageGroup;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PackageGroupController extends Controller
{
  public function index(Request $request)
  {
    $perPage = $request->input('perPage', 50);
    $totalEntries = PackageGroup::count();

    $packageGroups = PackageGroup::with('packages')->paginate($perPage);
    // return response()->json($packageGroups);

    return Inertia::render('F1F2PackageGroupList', [
      'packageGroups' => $packageGroups,
      // 'search' => $search,
      'perPage' => $perPage,
      'totalEntries' => $totalEntries,
    ]);
  }

  public function upsert($id = null) {}

  public function destroy($id) {}
}
