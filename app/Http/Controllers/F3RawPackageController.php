<?php

namespace App\Http\Controllers;

use App\Models\F3RawPackage;
use App\Models\F3PackageName;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\BulkUpserter;
use Illuminate\Http\Request;

class F3RawPackageController extends Controller
{
  private const SEARCHABLE_COLUMNS = [
    'raw_package',
    'lead_count',
    'dimension',
    'added_by',
  ];

  private function rules()
  {
    return [
      'raw_package' => 'required|string|max:100',
      'lead_count' => 'integer',
      'package_name' => 'required|string|exists:f3_package_names,package_name',
      'dimension' => 'string|max:50',
      'added_by' => 'string|max:7',
    ];
  }

  private function validateF3RawPackages(Request $request)
  {
    $data = $request->all();

    if (isset($data[0]) && is_array($data[0])) {
      return $request->validate(
        [
          '*.raw_package' => 'required|string|max:100',
          '*.lead_count' => 'integer',
          '*.package_name' => 'required|string|exists:f3_package_names,package_name',
          '*.dimension' => 'string|max:50',
          '*.added_by' => 'string|max:7',
        ]
      );
    }

    return $request->validate($this->rules());
  }

  public function bulkUpdate(Request $request)
  {
    $rows = $request->all();
    $user = session('emp_data');

    $columnRules = [
      'raw_package' => fn($id) => [
        'required',
        'string',
        Rule::unique('f3_raw_packages', 'raw_package')
          ->ignore(is_numeric($id) ? $id : null),
      ],
      'package_id' => fn($id) => [
        'required',
        'int',
        Rule::exists('f3_package_names', 'id'),
      ],
      'lead_count' => 'nullable|integer',
      'dimension' => 'nullable|string|max:50',
    ];

    $bulkUpdater = new BulkUpserter(new F3RawPackage(), $columnRules, [], []);

    $result = $bulkUpdater->update($rows, $user['emp_id'] ?? null);

    if (!empty($result['errors'])) {
      return response()->json([
        'status' => 'error',
        'message' => 'You have ' . count($result['errors']) . ' error/s',
        'data' => $result['errors']
      ], 422);
    }

    return response()->json([
      'status' => 'ok',
      'message' => 'Updated successfully',
      'updated' => $result['updated']
    ]);
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
    $validated = $this->validateF3RawPackages($request);

    $records = isset($validated[0]) ? $validated : [$validated];

    DB::beginTransaction();

    try {
      $results = [];

      foreach ($records as $index => $data) {

        $package = F3PackageName::where('package_name', $data['package_name'])
          ->firstOrFail();

        $data['package_id'] = $package->id;
        unset($data['package_name']);

        $rawPackage = $data['raw_package'];
        $rawPackageNormalized = str_replace(['-', '_'], '', $rawPackage);
        $data['raw_package_normalized'] = $rawPackageNormalized;

        $duplicateQuery = F3RawPackage::where('raw_package_normalized', $rawPackageNormalized);

        if ($id) {
          $duplicateQuery->where('id', '!=', $id);
        }

        if ($duplicateQuery->exists()) {
          $rowIndex = $index + 1;
          $row = count($records) === 1 ? '' : " (row {$rowIndex})";

          throw new \Exception(
            "The raw_package '{$rawPackage}' already exists{$row}."
          );
        }

        if ($id && count($records) === 1) {
          $f3RawPackage = F3RawPackage::findOrFail($id);
          $f3RawPackage->update($data);
        } else {
          $f3RawPackage = F3RawPackage::create($data);
        }

        $results[] = $f3RawPackage->load('f3_package_name');
      }

      DB::commit();

      return response()->json([
        'message' => count($results) > 1
          ? 'F3 raw packages processed successfully'
          : 'F3 raw package processed successfully',
        'data' => count($results) > 1 ? $results : $results[0],
      ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      DB::rollBack();
      return response()->json([
        'message' => 'Package name or F3 raw package not found.'
      ], 422);
    } catch (\Throwable $e) {
      DB::rollBack();
      return response()->json([
        'message' => $e->getMessage(),
        'error' => $e->getMessage()
      ], 500);
    }
  }

  public function store(Request $request)
  {
    return $this->upsertF3RawPackage($request);
  }

  public function update(Request $request, $id)
  {
    return $this->upsertF3RawPackage($request, $id);
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

  public function insertMany(Request $request)
  {
    $rawPackages = $request->input('raw_packages', []);

    $rawPackages = array_map(function ($p) {
      return [
        'raw_package' => $p['PACKAGE'] ?? '',
        'lead_count' => $p['lead_count'] ?? '',
        'package_name' => $p['package_name'] ?? '',
        'dimension' => $p['dimension'] ?? '',
      ];
    }, $rawPackages);

    return Inertia::render('F3RawPackageMultiUpsert', [
      'raw_packages' => $rawPackages,
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
    $perPage = $request->input('perPage', 50);
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

    if ($request->wantsJson()) {
      return response()->json([
        'f3RawPackages' => $f3RawPackages,
        'search' => $search,
        'perPage' => $perPage,
        'totalEntries' => $totalEntries,
      ]);
    }

    return Inertia::render('F3RawPackageList', [
      'f3RawPackages' => $f3RawPackages,
      'search' => $search,
      'perPage' => $perPage,
      'totalEntries' => $totalEntries,
    ]);
  }
}
