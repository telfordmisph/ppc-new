<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Facades\Log;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\ReaderInterface;

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

  /**
   * Core method to process and map headers
   */
  private function processHeaders(array $headerRow, array $expectedHeaders)
  {
    $headers = array_map([$this, 'normalize'], $headerRow);

    // Build canonical mapping
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

    // Log::info("headers: " . print_r($headers, true));
    // Log::info("map: " . print_r($map, true));
    // Log::info("found: " . print_r($found, true));

    $missing = array_diff(array_keys($expectedHeaders), $found);
    $valid = empty($missing);

    if (!$valid) {
      return [
        'status' => 'error',
        'errorType' => 'INVALID_HEADERS',
        'message' => 'Some required headers are missing or unrecognized.',
        'data' => [
          'missing_headers' => array_values($missing),
          'unknown_headers' => array_values($unknown),
          'found_headers' => $found,
        ],
      ];
    }

    return [
      'status' => 'success',
      'headers' => $headers,
      'found_headers' => $found,
      'map_headers' => $map,
    ];
  }

  /**
   * Get canonical headers from PhpSpreadsheet
   */
  public function getExcelCanonicalHeader(Spreadsheet $spreadsheet, array $expectedHeaders)
  {
    $sheet = $spreadsheet->getActiveSheet();
    $highestColumn = $sheet->getHighestColumn();

    $bestScore = -1;
    $headerRow = null;
    $headerRowIndex = 1;

    for ($row = 1; $row <= 10; $row++) {
      $currentRow = $sheet->rangeToArray("A{$row}:{$highestColumn}{$row}", null, true, true, false)[0];
      $score = $this->scoreHeaderRow($currentRow);

      if ($score > $bestScore) {
        $bestScore = $score;
        $headerRow = $currentRow;
        $headerRowIndex = $row;
      }
    }

    // fallback to first row if no header found
    if (!$headerRow) {
      $headerRow = $sheet->rangeToArray("A1:{$highestColumn}1", null, true, true, false)[0];
    }

    $result = $this->processHeaders($headerRow, $expectedHeaders);
    $result['headerRowIndex'] = $headerRowIndex;

    return $result;
  }

  /**
   * Get canonical headers from Spout CSV
   */
  public function getExcelCanonicalHeaderSpout(string $filePath, array $expectedHeaders)
  {
    $reader = ReaderEntityFactory::createCSVReader();
    $reader->open($filePath);

    $bestScore = -1;
    $headerRow = null;
    $headerRowIndex = 1;
    $currentRowIndex = 0;

    foreach ($reader->getSheetIterator() as $sheet) {
      foreach ($sheet->getRowIterator() as $row) {
        $currentRowIndex++;
        if ($currentRowIndex > 10) break;

        $cells = $row->toArray();
        $score = $this->scoreHeaderRow($cells);

        if ($score > $bestScore) {
          $bestScore = $score;
          $headerRow = $cells;
          $headerRowIndex = $currentRowIndex;
        }
      }
      break; // only first sheet
    }

    $reader->close();

    // fallback: first row
    if (!$headerRow) {
      $reader->open($filePath);
      foreach ($reader->getSheetIterator() as $sheet) {
        $headerRow = $sheet->getRowIterator()->current()->toArray();
        break;
      }
      $reader->close();
    }

    $result = $this->processHeaders($headerRow, $expectedHeaders);
    $result['headerRowIndex'] = $headerRowIndex;

    return $result;
  }
}
