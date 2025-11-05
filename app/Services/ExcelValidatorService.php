<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;

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
}
