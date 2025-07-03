<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        // Extract and sanitize request inputs
        $search         = $request->input('search'); // DO NOT ALTER
        $perPage        = (int) $request->input('perPage', 10); // DO NOT ALTER
        $sortBy         = $request->input('sortBy', ''); // CAN BE ALTERED
        $sortDirection  = $request->input('sortDirection', 'asc'); // CAN BE ALTERED
        $export         = $request->boolean('export'); // DO NOT ALTER
        $startDate      = $this->parseDate($request->input('start'), 'start'); // DO NOT ALTER
        $endDate        = $this->parseDate($request->input('end'), 'end'); // DO NOT ALTER

        // DB setup
        $connection = DB::connection('mysql'); // use mysql for the default connection
        $columns = $connection->getSchemaBuilder()->getColumnListing('admin'); // PUT TABLE NAME HERE TO GET COLUMN NAMES

        // Build query
        $query = $connection->table('admin') // PUT TABLE NAME HERE

            // FOR SEARCHING
            ->when($search, fn($q) => $this->applySearch($q, $columns, $search))

            // FOR DATE RANGE SEARCHING (change column name to check)
            ->when($startDate && $endDate, fn($q) => $q->whereBetween(DB::raw('DATE(NULL)'), [$startDate, $endDate]))

            // FOR SORTING
            ->when($sortBy, fn($q) => $q->orderBy($sortBy, $sortDirection));


        // Export CSV if requested
        if ($export) {
            return $this->exportCsv($query->get(), $columns);
        }

        // Return paginated Inertia view
        return Inertia::render('Admin/Admin', [
            'tableData' => $query->paginate($perPage)->withQueryString(),
            'tableFilters' => $request->only([
                'search',
                'perPage',
                'sortBy',
                'sortDirection',
                'start',
                'end',
            ]),
        ]);
    }

    private function parseDate(?string $date, string $type): ?string
    {
        if (!$date) return null;

        $carbon = Carbon::parse($date);
        return $type === 'start'
            ? $carbon->startOfDay()->format('Y-m-d')
            : $carbon->endOfDay()->format('Y-m-d');
    }

    private function applySearch($query, array $columns, string $search)
    {
        return $query->where(function ($q) use ($columns, $search) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', "%{$search}%");
            }
        });
    }

    private function exportCsv($data, array $columns)
    {
        $filename = 'employee_export_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data, $columns) {
            $output = fopen('php://output', 'w');
            fputcsv($output, $columns);

            foreach ($data as $row) {
                fputcsv($output, array_map(fn($col) => $row->$col ?? '', $columns));
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }
}
