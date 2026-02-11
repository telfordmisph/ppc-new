<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use App\Models\Machine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\BodySizeCapacityProfile;
use App\Services\BulkUpserter;
use Illuminate\Validation\Rule;
use App\Traits\MassDeletesByIds;

class MachineController extends Controller
{
  use MassDeletesByIds;
  public function __construct() {}

  private const SEARCHABLE_COLUMNS = [
    'name',
  ];

  public function bulkUpdate(Request $request)
  {
    $rows = $request->all();
    $user = session('emp_data');

    $columnRules = [
      'name' => fn($id) => [
        'sometimes',
        'required',
        'string',
        Rule::unique('machines', 'name')->ignore($id),
      ],
    ];

    $bulkUpdater = new BulkUpserter(new Machine(), $columnRules, [], []);

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

  public function massGenocide(Request $request)
  {
    return $this->massDeleteByIds(
      $request,
      Machine::class
    );
  }

  public function index(Request $request)
  {
    $search = $request->input('search', '');
    $perPage = $request->input('perPage', 25);
    $totalEntries = Machine::count();

    $machine = Machine::query()
      ->when($search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
          foreach (self::SEARCHABLE_COLUMNS as $column) {
            $q->orWhere($column, 'like', "%{$search}%");
          }
        });
      })
      ->orderBy('name')
      ->paginate($perPage)
      ->withQueryString();

    return Inertia::render('PackageBodySizeCapacityMachineList', [
      'machines' => $machine,
      'search' => $search,
      'perPage' => $perPage,
      'totalEntries' => $totalEntries,
    ]);
  }
}
