<?php

namespace App\Http\Controllers;

use App\Models\PartName;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;

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


    public function store(Request $request)
    {
        $validated = $this->validatePart($request);

        $part = PartName::create($validated);

        return response()->json([
            'message' => 'Part added successfully',
            'data' => $part,
        ]);
    }

    public function update(Request $request, $id)
    {
        $part = PartName::findOrFail($id);

        $validated = $this->validatePart($request, $id);
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
