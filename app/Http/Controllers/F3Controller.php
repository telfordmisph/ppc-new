<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use App\Models\F3;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Services\BulkUpserter;

class F3Controller extends Controller
{
  protected array $columnHandlers;

  public function __construct()
  {
    $this->columnHandlers = [
      'package' => function ($value) {
        return is_array($value) && isset($value['id']) ? $value['id'] : null;
      },
    ];
  }

  private const SEARCHABLE_COLUMNS = [
    'running_ct',
    'po_number',
    'machine_number',
    'part_number',
    'package_code',
    'package',
    'lot_number',
    'process_req',
    'qty',
    'good',
    'rej',
    'res',
    'doable',
    'gap_analysis',
    'cycle_time',
  ];

  private const dateColumns = [
    'date_loaded',
    'date_received',
    'date_commit',
    'actual_date_time'
  ];

  private const columnRules = [
    'running_ct' => 'nullable|numeric',
    'date_received' => 'nullable|date',
    'packing_list_srf' => 'nullable|string|max:20',
    'po_number' => 'nullable|string|max:15',
    'machine_number' => 'nullable|string|max:20',
    'part_number' => 'nullable|string|max:35',
    'package_code' => 'nullable|string|max:20',
    'package' => 'nullable|integer|exists:f3_raw_packages,id',
    'lot_number' => 'nullable|string|max:20',
    'process_req' => 'nullable|string|max:15',
    'qty' => 'nullable|integer',
    'good' => 'nullable|integer',
    'rej' => 'nullable|integer',
    'res' => 'nullable|integer',
    'date_commit' => 'nullable|date',
    'actual_date_time' => 'nullable|date',
    'status' => 'nullable|string|max:20',
    'do_number' => 'nullable|string|max:20',
    'remarks' => 'nullable|string',
    'doable' => 'nullable|integer',
    'focus_group' => 'nullable|string|max:10',
    'gap_analysis' => 'nullable|string|max:50',
    'cycle_time' => 'nullable|numeric',
    'imported_by' => 'nullable|string|max:7',
    'date_loaded' => 'nullable|date',
    'modified_at' => 'nullable|date',
    'modified_by' => 'nullable|string|max:7',
    'import_date' => 'nullable|date',
  ];

  public function bulkUpdate(Request $request)
  {
    $rows = $request->all();
    $user = session('emp_data');

    $bulkUpdater = new BulkUpserter(new F3(), self::columnRules, self::dateColumns, $this->columnHandlers);

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

  // public function bulkUpdate(Request $request)
  // {
  //   $rows = $request->all();
  //   $user = session('emp_data');

  //   DB::transaction(function () use ($rows, $user) {

  //     foreach ($rows as $id => $fields) {

  //       if (empty($fields)) {
  //         continue;
  //       }

  //       $model = F3::find($id);

  //       if (!$model) {
  //         continue;
  //       }

  //       $updateData = [];

  //       foreach ($fields as $column => $value) {

  //         // package relation → store only FK
  //         if ($column === 'package' && is_array($value)) {
  //           if (isset($value['id'])) {
  //             $updateData['package'] = $value['id'];
  //           }
  //           continue;
  //         }

  //         // date / datetime normalization
  //         if (in_array($column, self::date_columns)) {
  //           $updateData[$column] = $this->normalizeDate($value);
  //           continue;
  //         }

  //         // scalar field
  //         $updateData[$column] = $value;
  //       }

  //       $updateData['modified_by'] = $user['emp_id'] ?? null;

  //       if (!empty($updateData)) {
  //         $model->update($updateData);
  //       }
  //     }
  //   });

  //   return response()->json(['status' => 'ok']);
  // }

  private function looksLikeDate(string $value): bool
  {
    return preg_match('/^\d{4}-\d{2}-\d{2}/', $value);
  }

  private function normalizeDate(string $value)
  {
    Log::info('Normalizing date: ' . $value);

    try {
      if (strlen($value) === 10) {
        return Carbon::createFromFormat('Y-m-d', $value)
          ->toDateString();
      }

      if (strlen($value) === 16) {
        return Carbon::createFromFormat('Y-m-d H:i', str_replace('T', ' ', $value))
          ->toDateTimeString();
      }

      return Carbon::createFromFormat('Y-m-d H:i:s', $value)
        ->toDateTimeString();
    } catch (\Exception $e) {
      throw new \InvalidArgumentException('Some date format is invalid: ' . $value);
    }
  }

  private function validatePart(Request $request, $id = null)
  {
    return $request->validate([
      'Partname' => 'required|string|max:45',
      'Focus_grp' => 'required|string|max:45',
      'Factory' => 'required|string|max:10',
      'PL' => 'required|in:PL1,PL6',
      'Packagename' => 'required|string|max:45',
      'Leadcount' => 'required|string|max:45',
      'Bodysize' => 'required|string|max:45',
      'Packagecategory' => 'required|string|max:45',
    ]);
  }

  public function index(Request $request)
  {
    $search = $request->input('search', '');
    $perPage = $request->input('perPage', 25);
    $dateLoaded = $request->input('dateLoaded', null); // expects 'YYYY-MM-DD' format or null
    $statuses = $request->input('statuses');
    $totalEntries = F3::count();

    $f3WipAndOut = F3::query()
      ->with('package.f3_package_name')
      ->when($search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
          foreach (self::SEARCHABLE_COLUMNS as $column) {
            $q->orWhere($column, 'like', "%{$search}%");
          }

          $q->orWhereHas('package', function ($q2) use ($search) {
            $q2->where('raw_package', 'like', "%{$search}%");
          });
        });
      })

      ->when($dateLoaded, function ($query, $dateLoaded) {
        $start = date('Y-m-d 00:00:00', strtotime($dateLoaded));
        $end = date('Y-m-d 23:59:59', strtotime($dateLoaded));
        $query->whereBetween('date_loaded', [$start, $end]);
      })

      ->when(is_array($statuses) && count($statuses) > 0, function ($query) use ($statuses) {
        $query->whereIn('status', $statuses);
      })
      ->orderBy('date_loaded')
      ->paginate($perPage)
      ->withQueryString();

    return Inertia::render('F3List', [
      'f3WipAndOut' => $f3WipAndOut,
      'search' => $search,
      'perPage' => $perPage,
      'dateLoaded' => $dateLoaded,
      'totalEntries' => $totalEntries,
    ]);
  }
}
