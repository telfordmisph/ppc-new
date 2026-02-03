<?php

namespace App\Traits;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Facades\Log;

trait Sanitize
{
  protected function normalizeMonthNames(string $value): string
  {
    $map = [
      'junuary' => 'january',
      'januray' => 'january',
      'febuary' => 'february',
      'feburary' => 'february',
      'agust' => 'august',
      'sept' => 'september',
      'octuber' => 'october',
      'novemeber' => 'november',
      'decemeber' => 'december',
    ];

    $lower = strtolower($value);

    foreach ($map as $wrong => $correct) {
      if (str_contains($lower, $wrong)) {
        $value = str_ireplace($wrong, $correct, $value);
      }
    }

    return $value;
  }

  /**
   * Aggressive integer sanitizer
   * - Removes all whitespace, including non-breaking spaces
   * - Keeps only digits
   * - Returns integer if digits exist, null otherwise
   */
  protected function sanitizeInteger($value): ?int
  {
    if ($value === null) {
      return null;
    }

    $value = (string) $value;

    // Remove invisible/unicode spaces (\xC2\xA0, etc.)
    $value = str_replace(["\xC2\xA0", "\xc2\xa0", "\u00A0"], '', $value);
    // Remove everything except digits
    $digits = preg_replace('/\D/', '', $value);

    return $digits !== '' ? (int) $digits : null;
  }

  protected function sanitizeExcelCell($value)
  {
    if (!is_string($value)) {
      return $value;
    }

    // Remove BOM, zero-width, NBSP, and other invisible chars
    $value = preg_replace('/^\xEF\xBB\xBF/u', '', $value); // BOM
    $value = preg_replace('/[\p{C}\p{Zl}\p{Zp}]/u', '', $value); // invisible
    $value = trim(preg_replace('/\s+/u', ' ', $value));

    // Empty after cleanup
    if ($value === '') {
      return null;
    }

    // Normalize junk strings
    $junk = ['N/A', 'NA', 'NULL', 'null', '--', '-', '—', '.', '?'];
    if (in_array($value, $junk, true)) {
      return null;
    }

    return $value;
  }

  protected function getSanitizedSheetData(Spreadsheet $spreadsheet, $sheetToArrayArgs = [
    null,  // nullValue
    true,  // calculateFormulas
    false, // formatData
    false, // returnCellRef
    true,  // preserveEmptyRows
    false, // strictNullComparison
  ])
  {
    $sheet = $spreadsheet->getActiveSheet();
    $sheetData = $sheet->toArray(...$sheetToArrayArgs);

    $sheetData = array_map(
      fn($row) => array_map([$this, 'sanitizeExcelCell'], $row),
      $sheetData
    );

    $sheetData = array_filter($sheetData, fn($row) => array_filter($row, fn($cell) => $cell !== null));

    return array_values($sheetData);
  }
}
