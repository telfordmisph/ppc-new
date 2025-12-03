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

    public function autoImportWIP(Request $request)
    {
        $userId = $request->user()->id ?? null;
        $result = $this->wipImportService->autoImportF1F2WIP($userId);

        return response()->json([
            'status' => $result['status'] ?? 'success',
            'message' => $result['message'] ?? 'Import completed',
            'data' => $result['data'] ?? [],
        ]);
    }

    public function autoImportWIPOUTS(Request $request)
    {
        $userId = $request->user()->id ?? null;

        $result = $this->wipImportService->autoImportF1F2Outs($userId);

        Log::info("ttest", $result);

        return response()->json([
            'status' => $result['status'] ?? 'success',
            'message' => $result['message'] ?? 'Import completed',
            'total' => $result['total'] ?? [],
        ]);
    }

    public function importCapacity(Request $request)
    {
        $userId = $request->user()->id ?? null;


        $request->validate([
            'file' => 'required|file|mimes:xlsx|max:10240',
        ]);

        $file = $request->file('file');

        $result = $this->wipImportService->importCapacity($userId, $file);

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
        $userId = $user->id ?? null;

        $request->validate([
            'file' => 'required|file|mimes:xlsx|max:10240',
        ]);

        $file = $request->file('file');

        $result = $this->wipImportService->importF3WIP($userId, $file);

        return response()->json([
            'status' => $result['status'] ?? 'success',
            'message' => $result['message'] ?? 'Import completed',
            'data' => $result['data'] ?? [],
        ]);
    }

    public function importF3OUTS(Request $request)
    {
        $user = $request->user();
        $userId = $user->id ?? null;

        $request->validate([
            'file' => 'required|file|mimes:xlsx|max:10240',
        ]);

        $file = $request->file('file');

        $result = $this->wipImportService->importF3OUT($userId, $file);

        return response()->json([
            'status' => $result['status'] ?? 'success',
            'message' => $result['message'] ?? 'Import completed',
            'data' => $result['data'] ?? [],
        ]);
    }
}
