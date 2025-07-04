<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Services\DataTableService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminController extends Controller
{
    protected $datatable;

    public function __construct(DataTableService $datatable)
    {
        $this->datatable = $datatable;
    }

    public function index(Request $request)
    {
        $result = $this->datatable->handle(
            $request,
            'mysql', // connection
            'admin', // table
            [ // options
                'filename' => 'active_admins_export', // optional, for CSV export
                'defaultSortBy' => 'admin_id', // required
                'dateColumn'    => 'created_at', // default date column
                // 'joins' => [ // Comment out if not needed
                //     // Ex.
                //     [
                //         'type'    => 'leftJoin',
                //         'table'   => 'roles',
                //         'first'   => 'admin.role_id',
                //         'second'  => 'roles.id',
                //     ],
                // ],
                // 'conditions' => function ($query) { // Comment out if no conditions
                //     // Ex.
                //     return $query
                //         ->where('emp_name', 'test');
                // }
            ]
        );

        // FOR CSV EXPORTING (if needed)
        if ($result instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            return $result;
        }

        // FOR MASTERLIST DATA
        $resultMasterlist = $this->datatable->handle(
            $request,
            'masterlist', // connection
            'employee_masterlist', // table
            [ // options
                // 'filename' => 'active_admins_export', // optional, for CSV export
                'defaultSortBy' => 'EMPLOYID', // required
                'dateColumn'    => 'DATEHIRED', // default date column
                'conditions' => function ($query) { // Comment out if no conditions
                    // Ex.
                    return $query
                        ->whereNot('EMPLOYID', 0);
                }
            ]

        );

        return Inertia::render('Admin/Admin', [
            'tableData' => $result['data'],
            'tableFilters' => $request->only([
                'search',
                'perPage',
                'sortBy',
                'sortDirection',
                'start',
                'end',
            ]),

            'tableDataMasterlist' => $resultMasterlist['data'],
            'tableFiltersMasterlist' => $request->only([
                'search',
                'perPage',
                'sortBy',
                'sortDirection',
                'start',
                'end',
            ]),
        ]);
    }

    public function addAdmin(Request $request)
    {

        // dd($request->all());
        $checkIfExists = DB::table('admin')
            ->where('emp_id', $request->input('id'))
            ->exists();

        if (!$checkIfExists) {
            DB::table('admin')
                ->insert([
                    'emp_id' => $request->input('id'),
                    'emp_name' => $request->input('name'),
                    'emp_role' => $request->input('role'),
                    'last_updated_by' => session('emp_data')['emp_id'],
                ]);
        }

        return back()->with('success', 'Admin added successfully.');
    }

    public function removeAdmin(Request $request)
    {
        DB::table('admin')
            ->where('emp_id', $request->input('id'))
            ->delete();

        return back()->with('success', 'Admin removed successfully.');
    }

    public function changeAdminRole(Request $request)
    {
        $id = $request->input('id');
        $role = $request->input('role');

        DB::table('admin')
            ->where('emp_id', $id)
            ->update([
                'emp_role' => $role,
                'last_updated_by' => session('emp_data')['emp_id'],
            ]);

        // Update session data if the current user is the one whose role is being changed
        if (session('emp_data')['emp_id'] == $id) {
            $empData = session('emp_data');
            $empData['emp_system_role'] = $role;
            session()->put('emp_data', $empData);
        }


        return back()->with('success', 'Admin role changed successfully.');
    }
}
