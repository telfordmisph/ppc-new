<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Facades\Log;

class ExcelValidatorService
{
  public function isFileExists($filePath): bool
  {
    return file_exists($filePath);
  }

  public function isValidExcelFile($filePath): bool
  {
    $validExtensions = ['xls', 'xlsx', 'csv'];
    $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);

    return in_array(strtolower($fileExtension), $validExtensions);
  }

  private function looksLikeDate($value): bool
  {
    if (!is_string($value)) return false;
    // Simple date patterns (YYYY-MM-DD, MM/DD/YYYY, etc.)
    return preg_match('/\d{4}-\d{2}-\d{2}|\d{2}\/\d{2}\/\d{4}/', $value);
  }

  private function scoreHeaderRow(array $row): float
  {
    $nonEmpty = 0;
    $stringCells = 0;
    $numericCells = 0;

    Log::info("Row: " . print_r($row, true));

    foreach ($row as $cell) {
      if (!is_null($cell) && trim($cell) !== '') {
        $nonEmpty++;
        if (is_numeric($cell) || $this->looksLikeDate($cell)) {
          $numericCells++;
        } else {
          $stringCells++;
        }
      }
    }

    if ($nonEmpty === 0) {
      return -1; // empty row, ignore
    }

    // Score: prefer rows that are mostly strings
    return $stringCells / $nonEmpty;
  }

  private function normalize($value)
  {
    $value = preg_replace('/\-+/', ' ', $value);
    $value = preg_replace('/\s+/', ' ', $value);

    return strtolower(trim($value));
  }

  private function fuzzyMatch($header, $canonical)
  {
    $header = $this->normalize($header);

    $bestKey = null;
    $bestDistance = PHP_INT_MAX;

    foreach ($canonical as $aliasNorm => $key) {
      $dist = levenshtein($header, $aliasNorm);

      if ($dist < $bestDistance) {
        $bestDistance = $dist;
        $bestKey = $key;
      }
    }

    return $bestDistance <= 1 ? $bestKey : null;
  }

  public function getExcelCanonicalHeader(Spreadsheet $spreadsheet, array $expectedHeaders)
  {
    $sheet = $spreadsheet->getActiveSheet();
    $highestColumn = $sheet->getHighestColumn();
    $bestScore = -1;
    $headerRow = null;
    $headerRowIndex = 1;

    for ($row = 1; $row <= 10; $row++) {
      $currentRow = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, true, true, false)[0];
      $score = $this->scoreHeaderRow($currentRow);
      if ($score > $bestScore) {
        $bestScore = $score;
        $headerRow = $currentRow;
        $headerRowIndex = $row;
      }
    }

    $headers = null;
    if ($headerRow) {
      $headers = array_map([$this, 'normalize'], $headerRow);
    } else {
      $firstRow = $sheet->rangeToArray('A1:' . $highestColumn . '1', null, true, true, false)[0];
      $headers = array_map([$this, 'normalize'], $firstRow);
    }


    $canonical = [];
    foreach ($expectedHeaders as $key => $aliases) {
      foreach ($aliases as $alias) {
        $canonical[$this->normalize($alias)] = $key;
      }
    }


    $found = [];
    $unknown = [];
    $map = [];

    foreach ($headers as $idx => $h) {
      $hNorm = $this->normalize($h);
      if (isset($canonical[$hNorm])) {
        $found[] = $canonical[$hNorm];
        $map[] = $idx;
      } else {
        $match = $this->fuzzyMatch($hNorm, $canonical);
        if ($match !== null) {
          $found[] = $match;
          $map[] = $idx;
        } else {
          $unknown[] = $h;
        }
      }
    }

    \Log::info("canonical: " . print_r($found, true));

    $missing = array_diff(array_keys($expectedHeaders), $found);

    $valid = empty($missing);

    if (!$valid) {
      // handle invalid headers immediately
      return [
        'status' => 'error',
        'errorType' => 'INVALID_HEADERS',
        'message' => 'Some required headers are missing or unrecognized.',
        'data' => [
          'headerRowIndex' => $headerRowIndex,
          'missing' => array_values($missing),
          'unknown' => array_values($unknown),
          'found' => $found,
        ],
      ];
    }

    // valid case
    return [
      'status' => 'success',
      'headerRowIndex' => $headerRowIndex,
      'headers' => $headers,
      'found' => $found,
      'map' => $map,
    ];
  }
}
