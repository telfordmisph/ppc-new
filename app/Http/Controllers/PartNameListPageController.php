<?php

namespace App\Http\Controllers;

use App\Models\PartName;
use Inertia\Inertia;

use Illuminate\Http\Request;

class PartNameListPageController extends Controller
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

    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $perPage = $request->input('perPage', 10); // default to 10

        $totalEntries = PartName::count(); // total before search filter

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

    public function test(Request $request)
    {
        return to_route('part-name-list');
    }
}
