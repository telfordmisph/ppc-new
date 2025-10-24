<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WipImportService;
use Illuminate\Support\Facades\Log;


class AutoImportController extends Controller
{
    protected WipImportService $wipImportService;

    public function __construct(WipImportService $WipImportService)
    {
        $this->wipImportService = $WipImportService;
    }

    public function manualImport(Request $request)
    {
        $userId = $request->user()->id ?? null;

        $result = $this->wipImportService->autoImportWIP($userId);

        Log::info("ttest", $result);

        return response()->json([
            'status' => $result['status'] ?? 'success',
            'message' => $result['message'] ?? 'Import completed',
            'f1f2' => $result['f1f2'] ?? 0,
            'f3' => $result['f3'] ?? 0
        ]);
    }
}
