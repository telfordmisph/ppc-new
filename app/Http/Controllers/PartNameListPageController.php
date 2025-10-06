<?php

namespace App\Http\Controllers;

use App\Models\PartName;
use Inertia\Inertia;

use Illuminate\Http\Request;

class PartNameListPageController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');

        $partNames = PartName::query()
            ->when($search, function ($query, $search) {
                $query->where('Partname', 'like', "%{$search}%");
            })
            ->orderBy('Partname')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('PartNameList', [
            'partNames' => $partNames,
            'search' => $search,
        ]);
    }
}
