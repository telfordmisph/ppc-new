<?php

namespace App\Http\Controllers;

use App\Models\F3PackageName;
use Inertia\Inertia;

use Illuminate\Http\Request;

class F3PackageNamesController extends Controller
{
  private const SEARCHABLE_COLUMNS = [
    'package_name',
  ];

  private function validateF3PackageName(Request $request, $id = null)
  {
    return $request->validate([
      'package_name' => 'required|string|max:45',
    ]);
  }

  public function store(Request $request)
  {
    $validated = $this->validateF3PackageName($request);

    $f3PackageName = F3PackageName::create($validated);

    return response()->json([
      'message' => 'F3 raw package added successfully',
      'data' => $f3PackageName,
    ]);
  }

  public function update(Request $request, $id)
  {
    $f3PackageName = F3PackageName::findOrFail($id);

    $validated = $this->validateF3PackageName($request, $id);
    $f3PackageName->update($validated);

    return response()->json([
      'message' => 'F3 package updated successfully',
      'data' => $f3PackageName,
    ]);
  }


  public function upsert($id = null)
  {
    $f3PackageName = $id ? F3PackageName::findOrFail($id) : null;

    return Inertia::render('F3PackageNameUpsert', [
      'part' => $f3PackageName,
    ]);
  }

  public function destroy($id)
  {
    $f3PackageName = F3PackageName::findOrFail($id);
    $f3PackageName->delete();

    return response()->json([
      'success' => true,
      'message' => 'F3 package deleted successfully',
    ]);
  }

  public function index(Request $request)
  {
    $search = $request->input('search', '');
    $perPage = $request->input('perPage', 10);
    $totalEntries = F3PackageName::count();

    $f3PackageNames = F3PackageName::query()
      ->when($search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
          foreach (self::SEARCHABLE_COLUMNS as $column) {
            $q->orWhere($column, 'like', "%{$search}%");
          }
        });
      })
      ->orderBy('package_name')
      ->paginate($perPage)
      ->withQueryString();

    return Inertia::render('F3PackageNameList', [
      'f3PackageNames' => $f3PackageNames,
      'search' => $search,
      'perPage' => $perPage,
      'totalEntries' => $totalEntries,
    ]);
  }

  public function getAll()
  {
    $f3PackageNames = F3PackageName::all();
    return response()->json($f3PackageNames);
  }
}
