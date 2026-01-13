<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WipImportService;
use Exception;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AutoImportController extends Controller
{
    protected WipImportService $wipImportService;

    public function __construct(WipImportService $WipImportService)
    {
        $this->wipImportService = $WipImportService;
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
        return Inertia::render('Import/PickUpImportPage');
    }

    public function ftpRootImportWIP(Request $request)
    {
        $empId = $request->get('emp_id');

        $result = $this->wipImportService->ftpRootImportF1F2WIP($empId);

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


        $result = $this->wipImportService->ftpRootImportF1F2Outs($empId);

        Log::info("ttest", $result);

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
        Log::info("manual import wip");
        $result = $this->wipImportService->importF1F2WIP($empId, $file);

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

        $result = $this->wipImportService->importF1F2OUTS($empId, $file);

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

        $result = $this->wipImportService->importCapacity($empId, $file);

        return response()->json([
            'status' => $result['status'] ?? 'success',
            'message' => $result['message'] ?? 'Import completed',
            'data' => $result['data'] ?? [],
            'updated' => $result['updated'] ?? [],
            'created' => $result['created'] ?? [],
            'total' => $result['total'] ?? [],
        ]);
    }

    public function importF3WIP(Request $request)
    {
        $user = $request->user();
        $empId = $request->get('emp_id');


        $request->validate([
            'file' => 'required|file|mimes:xlsx|max:10240',
        ]);

        $file = $request->file('file');

        $result = $this->wipImportService->importF3WIP($empId, $file);

        return response()->json([
            'status' => $result['status'] ?? 'success',
            'message' => $result['message'] ?? 'Import completed',
            'data' => $result['data'] ?? [],
        ]);
    }

    public function importPickUp(Request $request)
    {
        $user = $request->user();
        $empId = $request->get('emp_id');

        $request->validate([
            'file' => 'required|file|mimes:xlsx|max:10240',
        ]);

        $file = $request->file('file');

        $result = $this->wipImportService->importPickUp($empId, $file);

        return response()->json([
            'status' => $result['status'] ?? 'success',
            'message' => $result['message'] ?? 'Import completed',
            'data' => $result['data'] ?? [],
        ]);
    }

    public function importF3OUTS(Request $request)
    {
        $user = $request->user();
        $empId = $request->get('emp_id');


        $request->validate([
            'file' => 'required|file|mimes:xlsx|max:10240',
        ]);

        $file = $request->file('file');

        $result = $this->wipImportService->importF3OUT($empId, $file);

        return response()->json([
            'status' => $result['status'] ?? 'success',
            'message' => $result['message'] ?? 'Import completed',
            'data' => $result['data'] ?? [],
        ]);
    }

    public function importF3(Request $request)
    {
        $user = $request->user();
        $empId = $request->get('emp_id');


        $request->validate([
            'file' => 'required|file|mimes:xlsx|max:10240',
        ]);

        $file = $request->file('file');

        $result = $this->wipImportService->importF3($empId, $file);

        return response()->json([
            'status' => $result['status'] ?? 'success',
            'message' => $result['message'] ?? 'Import completed',
            'data' => $result['data'] ?? [],
        ]);
    }
}
