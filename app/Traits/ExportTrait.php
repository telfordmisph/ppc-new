<?php

namespace App\Traits;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;

trait ExportTrait
{
  protected function downloadRawXlsx(array $sheets, string $filenamePrefix)
  {
    $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');

    $writer = WriterEntityFactory::createXLSXWriter();
    $writer->setShouldCreateNewSheetsAutomatically(false);
    $writer->openToFile($tempFile);

    $style = (new StyleBuilder())
      ->setShouldWrapText(false)
      ->build();

    $firstSheet = true;
    foreach ($sheets as $sheetName => $queryFn) {
      $rows = $queryFn();

      if (!$rows->count()) {
        continue;
      }

      if ($firstSheet) {
        $writer->getCurrentSheet()->setName($sheetName);
        $firstSheet = false;
      } else {
        $writer->addNewSheetAndMakeItCurrent();
        $writer->getCurrentSheet()->setName($sheetName);
      }

      // Write header row
      $firstRow = $rows->first();
      $columns = array_keys((array) $firstRow);
      $writer->addRow(WriterEntityFactory::createRowFromArray($columns, $style));

      // Stream each row
      foreach ($rows as $row) {
        $writer->addRow(WriterEntityFactory::createRowFromArray((array) $row, $style));
      }
    }

    $writer->close();

    $headers = ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    $response = response()->download($tempFile, "{$filenamePrefix}_" . now()->format('Ymd_His') . ".xlsx", $headers);
    ob_end_clean();
    return $response->deleteFileAfterSend(true);
  }
}
