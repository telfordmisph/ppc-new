<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ImportService;
use Exception;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AutoImportController extends Controller
{
    protected ImportService $importService;

    public function __construct(ImportService $importService)
    {
        $this->importService = $importService;
    }

    public function renderF1F2ImportPage()
    {
        return Inertia::render('Import/F1F2ImportPage');
    }

    public function renderF3ImportPage()
    {
        return Inertia::render('Import/F3ImportPage');
    }

    public function renderPickUpImportPage()
    {
        return Inertia::render('Import/F1F2PickUpImportPage');
    }

    public function renderF3PickUpImportPage()
    {
        return Inertia::render('Import/F3PickUpImportPage');
    }

    public function ftpRootImportWIP(Request $request)
    {
        $empId = $request->get('emp_id');

        $result = $this->importService->ftpRootImportF1F2WIP($empId);

        return response()->json([
            'status' => $result['status'] ?? 'success',
            'message' => $result['message'] ?? 'Import completed',
            'data' => $result['data'] ?? [],
            'total' => $result['total'] ?? [],
        ]);
    }

    public function ftpRootImportOUTS(Request $request)
    {
        $empId = $request->get('emp_id');

        $result = $this->importService->ftpRootImportF1F2OUT($empId);

        return response()->json([
            'status' => $result['status'] ?? 'success',
            'message' => $result['message'] ?? 'Import completed',
            'data' => $result['data'] ?? [],
            'total' => $result['total'] ?? [],
        ]);
    }

    public function manualImportWIP(Request $request)
    {
        $empId = $request->get('emp_id');
        $request->validate([
            'file' => 'required|file|mimes:csv|max:50000',
        ]);
        $file = $request->file('file');
        $result = $this->importService->importF1F2WIP($empId, $file);

        return response()->json([
            'status' => $result['status'] ?? 'success',
            'message' => $result['message'] ?? 'Import completed',
            'data' => $result['data'] ?? [],
            'total' => $result['total'] ?? [],
        ]);
    }

    public function manualImportOUTS(Request $request)
    {
        $empId = $request->get('emp_id');
        $request->validate([
            'file' => 'required|file|mimes:csv|max:50000',
        ]);
        $file = $request->file('file');

        $result = $this->importService->importF1F2OUT($empId, $file);

        return response()->json([
            'status' => $result['status'] ?? 'success',
            'message' => $result['message'] ?? 'Import completed',
            'data' => $result['data'] ?? [],
            'total' => $result['total'] ?? [],
        ]);
    }

    public function importCapacity(Request $request)
    {
        $empId = $request->get('emp_id');



        $request->validate([
            'file' => 'required|file|mimes:xlsx|max:10240',
        ]);

        $file = $request->file('file');

        $result = $this->importService->importCapacity($empId, $file);

        return response()->json([
            'status' => $result['status'] ?? 'success',
            'message' => $result['message'] ?? 'Import completed',
            'data' => $result['data'] ?? [],
            'updated' => $result['updated'] ?? [],
            'created' => $result['created'] ?? [],
            'total' => $result['total'] ?? [],
        ]);
    }

    public function importPickUp(Request $request)
    {
        $user = $request->user();
        $empId = $request->get('emp_id');

        $isAllowDuplicate = filter_var($request->input('isAllowDuplicate', false), FILTER_VALIDATE_BOOLEAN);

        $request->validate([
            'file' => 'required|file|mimes:xlsx|max:10240',
        ]);

        $file = $request->file('file');

        $result = $this->importService->importF1F2PickUp($empId, $file, $isAllowDuplicate);

        return response()->json([
            'status' => $result['status'] ?? 'success',
            'message' => $result['message'] ?? 'Import completed',
            'data' => $result['data'] ?? [],
        ], $result['status'] === 'success' ? 200 : 422);
    }

    public function importF3PickUp(Request $request)
    {
        $user = $request->user();
        $empId = $request->get('emp_id');
        $isAllowDuplicate = filter_var($request->input('isAllowDuplicate', false), FILTER_VALIDATE_BOOLEAN);

        $request->validate([
            'file' => 'required|file|mimes:xlsx|max:10240',
        ]);

        $file = $request->file('file');

        $result = $this->importService->importF3PickUp($empId, $file, $isAllowDuplicate);

        return response()->json([
            'status' => $result['status'] ?? 'success',
            'message' => $result['message'] ?? 'Import completed',
            'data' => $result['data'] ?? [],
        ], $result['status'] === 'success' ? 200 : 422);
    }

    public function importF3(Request $request)
    {
        $user = $request->user();
        $empId = $request->get('emp_id');


        $request->validate([
            'file' => 'required|file|mimes:xlsx|max:10240',
        ]);

        $file = $request->file('file');

        $result = $this->importService->importF3($empId, $file);

        return response()->json([
            'status' => $result['status'] ?? 'success',
            'message' => $result['message'] ?? 'Import completed',
            'data' => $result['data'] ?? [],
        ]);
    }
}
