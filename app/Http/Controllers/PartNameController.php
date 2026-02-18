<?php

namespace App\Http\Controllers;

use App\Models\PartName;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Services\BulkUpserter;

class PartNameController extends Controller
{
    private const SEARCHABLE_COLUMNS = [
        'Partname',
        'Focus_grp',
        'Factory',
        'PL',
        'Packagename',
        'Packagecategory',
        'Leadcount',
        'Bodysize',
        'Package',
        'added_by',
    ];

    private function rules(): array
    {
        return [
            'Partname' => 'required|string|max:45',
            'Focus_grp' => 'required|string|max:45',
            'Factory' => 'required|string|max:10',
            'PL' => 'required|in:PL1,PL6',
            'Packagename' => 'required|string|max:45',
            'Leadcount' => 'required|string|max:45',
            'Bodysize' => 'required|string|max:45',
            'Packagecategory' => 'required|string|max:45',
        ];
    }

    private function validateParts(Request $request): array
    {
        $data = $request->all();

        if (isset($data[0]) && is_array($data[0])) {
            return $request->validate([
                '*.Partname' => 'required|string|max:45',
                '*.Focus_grp' => 'required|string|max:45',
                '*.Factory' => 'required|string|max:10',
                '*.PL' => 'required|in:PL1,PL6',
                '*.Packagename' => 'required|string|max:45',
                '*.Leadcount' => 'required|string|max:45',
                '*.Bodysize' => 'required|string|max:45',
                '*.Packagecategory' => 'required|string|max:45',
            ]);
        }

        return $request->validate($this->rules());
    }

    public function store(Request $request)
    {
        $user = session('emp_data');
        $addedBy = $user['emp_id'] ?? null;

        $validated = $this->validateParts($request);

        $records = isset($validated[0]) ? $validated : [$validated];

        $records = array_map(function ($part) use ($addedBy) {
            return array_merge($part, [
                'added_by' => $addedBy,
            ]);
        }, $records);

        PartName::insert($records);

        return response()->json([
            'message' => 'Part(s) added successfully',
            'data' => $records,
        ]);
    }


    public function update(Request $request, $id)
    {
        $part = PartName::findOrFail($id);

        $validated = $this->validateParts($request);
        $part->update($validated);

        return response()->json([
            'message' => 'Part updated successfully',
            'data' => $part,
        ]);
    }


    public function upsert($id = null)
    {
        $part = $id ? PartName::findOrFail($id) : null;

        return Inertia::render('PartNameUpsert', [
            'part' => $part,
        ]);
    }

    public function insertMany(Request $request)
    {
        $parts = $request->input('parts', []);

        $parts = array_map(function ($p) {
            return [
                'Partname' => $p['PARTNAME'] ?? '',
                'Focus_grp' => $p['Focus_grp'] ?? '',
                'Factory' => $p['Factory'] ?? '',
                'PL' => $p['PL'] ?? 'PL1',
                'Packagename' => $p['Packagename'] ?? '',
                'Leadcount' => $p['Leadcount'] ?? '',
                'Bodysize' => $p['Bodysize'] ?? '',
                'Packagecategory' => $p['Packagecategory'] ?? '',
            ];
        }, $parts);

        return Inertia::render('PartNameMultiUpsert', [
            'parts' => $parts,
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $rows = $request->all();
        $user = session('emp_data');

        $columnRules = [
            'Focus_grp' => 'nullable',
            'Factory' => 'nullable',
            'PL' => 'nullable',
            'Partname' => 'nullable',
            'Packagename' => 'nullable',
            'Packagecategory' => 'nullable',
            'Leadcount' => 'nullable',
            'Bodysize' => 'nullable',
        ];

        $bulkUpdater = new BulkUpserter(new PartName(), $columnRules, [], []);

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

    public function destroy($id)
    {
        $part = PartName::findOrFail($id);
        $part->delete();

        return response()->json([
            'success' => true,
            'message' => 'Part deleted successfully',
        ]);
    }

    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $perPage = $request->input('perPage', 10);
        $totalEntries = PartName::count();

        $partNames = PartName::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    foreach (self::SEARCHABLE_COLUMNS as $column) {
                        $q->orWhere($column, 'like', "%{$search}%");
                    }
                });
            })
            ->orderBy('Partname')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('PartNameList', [
            'partNames' => $partNames,
            'search' => $search,
            'perPage' => $perPage,
            'totalEntries' => $totalEntries,
        ]);
    }
}
