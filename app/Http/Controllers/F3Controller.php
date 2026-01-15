<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use App\Models\F3;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class F3Controller extends Controller
{
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

  public function bulkUpdate(Request $request)
  {
    $rows = $request->all();
    $user = session('emp_data');

    DB::transaction(function () use ($rows, $user) {

      foreach ($rows as $id => $fields) {

        if (empty($fields)) {
          continue;
        }

        $model = F3::find($id);

        if (!$model) {
          continue;
        }

        $updateData = [];

        foreach ($fields as $column => $value) {

          // package relation → store only FK
          if ($column === 'package' && is_array($value)) {
            if (isset($value['id'])) {
              $updateData['package'] = $value['id'];
            }
            continue;
          }

          // date / datetime normalization
          if (is_string($value) && $this->looksLikeDate($value)) {
            $updateData[$column] = $this->normalizeDate($value);
            continue;
          }

          // scalar field
          $updateData[$column] = $value;
        }

        $updateData['modified_by'] = $user['emp_id'] ?? null;

        if (!empty($updateData)) {
          $model->update($updateData);
        }
      }
    });

    return response()->json(['status' => 'ok']);
  }

  private function looksLikeDate(string $value): bool
  {
    return preg_match('/^\d{4}-\d{2}-\d{2}/', $value);
  }

  private function normalizeDate(string $value)
  {
    // date-only
    if (strlen($value) === 10) {
      return Carbon::createFromFormat('Y-m-d', $value)
        ->toDateString();
    }

    // datetime
    return Carbon::createFromFormat('Y-m-d H:i:s', $value)
      ->toDateTimeString();
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
