<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PpcPackagePlRule;
use App\Services\BulkUpserter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Traits\MassDeletesByIds;

// ─────────────────────────────────────────────
// Package Master Controller
// ─────────────────────────────────────────────
class PlRuleController extends Controller
{
  use MassDeletesByIds;

  public function index(Request $request)
  {
    $search  = $request->input('search', '');
    $perPage = $request->input('perPage', 25);

    $rules = PpcPackagePlRule::query()
      ->when($search, fn($q) => $q->where('package', 'like', "%{$search}%")
        ->orWhere('note', 'like', "%{$search}%"))
      ->orderBy('package')
      ->orderBy('priority')
      ->paginate($perPage)
      ->through(fn($rule) => [
        ...$rule->toArray(),
        'updated_at' => $rule->updated_at?->format('M d, Y h:i A'),
      ])
      ->withQueryString();

    return Inertia::render('PpcPackagePlRulesPage', [
      'rules'        => $rules,
      'search'       => $search,
      'perPage'      => $perPage,
      'totalEntries' => PpcPackagePlRule::count(),
    ]);
  }

  public function bulkUpdate(Request $request)
  {
    $rows = $request->all();

    $columnRules = [
      'package'         => fn($id) => ['sometimes', 'required', 'string', 'max:45'],
      'production_line' => fn($id) => ['sometimes', 'required', 'in:PL1,PL6'],
      'factory'         => ['nullable', 'in:F1,F2,F3'],
      'lead_count'      => ['nullable', 'integer'],
      'partname_like'   => ['nullable', 'string', 'max:100'],
      'priority'        => fn($id) => ['sometimes', 'required', 'integer'],
      'is_active'       => fn($id) => ['sometimes', 'required', 'integer'],
      'valid_from'      => ['nullable', 'date'],
      'valid_to'        => ['nullable', 'date'],
      'note'            => ['nullable', 'string', 'max:255'],
    ];

    $bulkUpdater = new BulkUpserter(new PpcPackagePlRule(), $columnRules);
    $result = $bulkUpdater->update($rows);

    if (!empty($result['errors'])) {
      return response()->json([
        'status'  => 'error',
        'message' => 'You have ' . count($result['errors']) . ' error/s',
        'data'    => $result['errors'],
      ], 422);
    }

    return response()->json([
      'status'  => 'ok',
      'message' => 'Updated successfully',
      'updated' => $result['updated'],
    ]);
  }

  public function massDelete(Request $request)
  {
    return $this->massDeleteByIds($request, PpcPackagePlRule::class);
  }
}
