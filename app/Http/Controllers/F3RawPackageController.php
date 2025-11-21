<?php

namespace App\Http\Controllers;

use App\Models\F3RawPackage;
use App\Models\F3PackageName;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class F3RawPackageController extends Controller
{
  private const SEARCHABLE_COLUMNS = [
    'raw_package',
    'lead_count',
    'dimension',
    'added_by',
  ];

  private function validateF3RawPackage(Request $request, $id = null)
  {
    return $request->validate([
      'raw_package' => 'required|string|max:100',
      'lead_count' => 'integer',
      'package_name' => 'required|string|exists:f3_package_names,package_name', // validate existence
      'dimension' => 'string|max:50',
      'added_by' => 'string|max:7',
    ]);
  }

  public function store(Request $request)
  {
    return $this->upsertF3RawPackage($request);
  }

  public function update(Request $request, $id)
  {
    return $this->upsertF3RawPackage($request, $id);
  }

  /**
   * Handle creating or updating a F3RawPackage
   *
   * @param Request $request
   * @param int|null $id
   * @return \Illuminate\Http\JsonResponse
   */
  private function upsertF3RawPackage(Request $request, ?int $id = null)
  {
    $validated = $this->validateF3RawPackage($request, $id);

    DB::beginTransaction();

    try {
      $package = F3PackageName::where('package_name', $validated['package_name'])->firstOrFail();

      $validated['package_id'] = $package->id;
      unset($validated['package_name']);

      if ($id) {
        $f3RawPackage = F3RawPackage::findOrFail($id);
        $f3RawPackage->update($validated);
        $message = 'F3 raw package updated successfully';
      } else {
        $f3RawPackage = F3RawPackage::create($validated);
        $message = 'F3 raw package added successfully';
      }

      $f3RawPackage->load('f3_package_name');

      DB::commit();

      return response()->json([
        'message' => $message,
        'data' => $f3RawPackage,
      ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      DB::rollBack();
      return response()->json([
        'message' => 'Package name or F3 raw package not found.'
      ], 422);
    } catch (\Throwable $e) {
      DB::rollBack();
      return response()->json([
        'message' => 'Failed to save F3 raw package',
        'error' => $e->getMessage()
      ], 500);
    }
  }


  public function upsert($id = null)
  {
    $f3RawPackage = $id
      ? F3RawPackage::with('f3_package_name')->findOrFail($id)
      : null;

    return Inertia::render('F3RawPackageUpsert', [
      'selectedRawPackage' => $f3RawPackage,
    ]);
  }

  public function destroy($id)
  {
    $f3RawPackage = F3RawPackage::findOrFail($id);
    $f3RawPackage->delete();

    return response()->json([
      'success' => true,
      'message' => 'F3 Raw package deleted successfully',
    ]);
  }

  public function index(Request $request)
  {
    $search = $request->input('search', '');
    $perPage = $request->input('perPage', 10);
    $totalEntries = F3RawPackage::count();

    $f3RawPackages = F3RawPackage::query()
      ->with('f3_package_name')
      ->when($search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
          foreach (self::SEARCHABLE_COLUMNS as $column) {
            $q->orWhere($column, 'like', "%{$search}%");
          }

          $q->orWhereHas('f3_package_name', function ($rel) use ($search) {
            $rel->where('package_name', 'like', "%{$search}%");
          });
        });
      })
      ->orderBy('raw_package')
      ->paginate($perPage)
      ->withQueryString();

    return Inertia::render('F3RawPackageList', [
      'f3RawPackages' => $f3RawPackages,
      'search' => $search,
      'perPage' => $perPage,
      'totalEntries' => $totalEntries,
    ]);
  }
}
