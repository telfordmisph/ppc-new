<?php

namespace App\Traits;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;

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

  protected function exportRawXlsxSpout(array $sheets, string $fileName)
  {
    $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');

    $writer = WriterEntityFactory::createXLSXWriter();
    $writer->setShouldCreateNewSheetsAutomatically(false); // optional
    $writer->openToFile($tempFile);

    $style = (new StyleBuilder())
      ->setShouldWrapText(false)
      ->build();

    $firstSheet = true;
    foreach ($sheets as $title => $rows) {
      if ($rows->isEmpty()) {
        continue;
      }

      if ($firstSheet) {
        $writer->getCurrentSheet()->setName($title);
        $firstSheet = false;
      } else {
        $writer->addNewSheetAndMakeItCurrent();
        $writer->getCurrentSheet()->setName($title);
      }

      $columns = (array) $rows->first();
      $headerRow = WriterEntityFactory::createRowFromArray(array_keys($columns), $style);
      $writer->addRow($headerRow);

      foreach ($rows as $row) {
        $rowData = WriterEntityFactory::createRowFromArray((array) $row, $style);
        $writer->addRow($rowData);
      }
    }

    $writer->close();

    if (empty($sheets)) {
      return null;
    }

    $headers = ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    $response = response()->download($tempFile, $fileName, $headers);
    ob_end_clean();
    return $response->deleteFileAfterSend(true);
  }
}
