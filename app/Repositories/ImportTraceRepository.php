<?php

namespace App\Repositories;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportTraceRepository
{
  private const TABLE = 'latest_imports';
  private const TYPES = ['f1f2_wip', 'f1f2_out', 'f3_wip', 'f3_out', 'capacity', 'f3', 'pickup', 'f3_pickup'];

  // Get latest import for any type
  public function getImport($type)
  {
    $this->validateType($type);

    $import = DB::table(self::TABLE)
      ->where('import_type', $type)
      ->first();

    return response()->json($import);
  }

  // Upsert latest import for any type
  public function upsertImport(string $type, ?int $imported_by = null, int $entries = 0)
  {
    $this->validateType($type);

    DB::table(self::TABLE)->updateOrInsert(
      ['import_type' => $type],
      [
        'latest_import' => now(),
        'imported_by' => $imported_by,
        'entries' => $entries
      ]
    );

    return true;
  }


  // Optional helper to validate allowed types
  private function validateType($type)
  {
    if (!in_array($type, self::TYPES)) {
      abort(400, 'Invalid import type.');
    }
  }

  public function getAllLatestImports()
  {
    $latestImports = DB::table('latest_imports')
      ->whereIn('import_type', self::TYPES)
      ->get()
      ->keyBy('import_type');

    return response()->json($latestImports);
  }
}
