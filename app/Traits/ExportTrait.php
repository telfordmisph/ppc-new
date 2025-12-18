<?php

namespace App\Traits;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

trait ExportTrait
{
  protected function exportRawXlsx(array $sheets, string $fileName)
  {
    $spreadsheet = new Spreadsheet();
    $sheetIndex = 0;

    foreach ($sheets as $title => $rows) {
      if ($rows->isEmpty()) {
        continue;
      }

      $sheet = $sheetIndex === 0
        ? $spreadsheet->getActiveSheet()
        : $spreadsheet->createSheet();

      $sheet->setTitle($title);

      $columns = array_keys((array) $rows->first());
      $sheet->fromArray($columns, null, 'A1');

      $data = $rows->map(fn($row) => (array) $row)->toArray();
      $sheet->fromArray($data, null, 'A2');

      $sheetIndex++;
    }

    if ($sheetIndex === 0) {
      return null;
    }

    $writer = new Xlsx($spreadsheet);
    $writer->setPreCalculateFormulas(false);

    $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');
    $writer->save($tempFile);

    $headers = ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',];
    $response = response()->download($tempFile, $fileName, $headers);
    ob_end_clean();
    return $response->deleteFileAfterSend(true);
  }
}
