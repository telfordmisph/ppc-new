<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    // public function index(Request $request)
    // {
    //     $search = $request->input('search');
    //     $perPage = (int) $request->input('perPage', 10);
    //     $sortBy = $request->input('sortBy', 'EMPNAME');
    //     $sortDirection = $request->input('sortDirection', 'asc');

    //     $dateFrom = $request->input('from');
    //     $dateTo = $request->input('to');


    //     // dd($request->all());
    //     // $emplist = DB::connection('masterlist')
    //     //     ->table('employee_masterlist')
    //     //     ->when(
    //     //         $search,
    //     //         fn($q) =>
    //     //         $q->where('EMPNAME', 'like', "%{$search}%")
    //     //     )
    //     //     ->orderBy($sortBy, $sortDirection)
    //     //     ->paginate($perPage)
    //     //     ->withQueryString();

    //     $query = DB::connection('masterlist')
    //         ->table('employee_masterlist')
    //         ->when($search, function ($q) use ($search) {
    //             $q->where('EMPNAME', 'like', "%{$search}%");
    //         })
    //         ->when($dateFrom && $dateTo, function ($q) use ($dateFrom, $dateTo) {
    //             $q->whereBetween('DATEHIRED', [$dateFrom, $dateTo]); // Use correct datetime column
    //         })
    //         ->orderBy($sortBy, $sortDirection);

    //     $data = ($dateFrom && $dateTo)
    //         ? $query->get() // No pagination for date range
    //         : $query->paginate($perPage)->withQueryString();


    //     dd($data);
    //     return Inertia::render('Dashboard', [
    //         'data' => $data,
    //         'filters' => $request->only('search', 'perPage', 'sortBy', 'sortDirection'),
    //     ]);
    // }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('perPage', 10);
        $sortBy = $request->input('sortBy', 'EMPNAME');
        $sortDirection = $request->input('sortDirection', 'asc');

        // Normalize date range format for MySQL datetime columns
        $dateFrom = $request->input('from') ? date('Y-m-d 00:00:00', strtotime($request->input('from'))) : null;
        $dateTo = $request->input('to') ? date('Y-m-d 23:59:59', strtotime($request->input('to'))) : null;

        $query = DB::connection('masterlist')
            ->table('employee_masterlist')
            ->when(
                $search,
                fn($q) =>
                $q->where('EMPNAME', 'like', "%{$search}%")
            )
            ->when(
                $dateFrom && $dateTo,
                fn($q) =>
                $q->whereBetween('DATEHIRED', [$dateFrom, $dateTo])
            )
            ->orderBy($sortBy, $sortDirection);

        // Always paginate
        // $data = $query->paginate($perPage)->withQueryString();

        $data = $dateFrom && $dateTo
            ? $query->get()
            : $query->paginate($perPage)->withQueryString();


        return Inertia::render('Dashboard', [
            'data' => $data,
            'filters' => $request->only([
                'search',
                'perPage',
                'sortBy',
                'sortDirection',
                'from',
                'to'
            ]),
        ]);
    }
}
