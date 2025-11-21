<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ImportTraceRepository;

class ImportTraceController extends Controller
{
  protected ImportTraceRepository $importTraceRepo;

  public function __construct(ImportTraceRepository $importTraceRepo)
  {
    $this->importTraceRepo = $importTraceRepo;
  }

  public function getImport($type)
  {
    $import = $this->importTraceRepo->getImport($type);

    return $import;
  }

  public function upsertImport(Request $request, $type)
  {
    $request->validate([
      'imported_by' => 'nullable|integer',
      'entries' => 'nullable|integer'
    ]);

    $this->importTraceRepo->upsertImport(
      $type,
      $request->imported_by ?? null,
      $request->entries ?? 0
    );

    return response()->json(['status' => 'success']);
  }

  public function getAllLatestImports()
  {
    return $this->importTraceRepo->getAllLatestImports();
  }
}
