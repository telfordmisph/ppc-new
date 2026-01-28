<?php

namespace App\Services;

use App\Repositories\F1F2WipRepository;
use App\Repositories\F1F2OutRepository;
use App\Services\ExcelValidatorService;
use App\Services\PackageCapacityService;
use App\Repositories\F3RawPackageRepository;
use App\Repositories\ImportTraceRepository;
use App\Repositories\PickUpRepository;
use App\Traits\ParseDateTrait;
use App\Constants\WipConstants;
use App\Repositories\F3OutRepository;
use App\Repositories\F3WipRepository;
use App\Traits\Sanitize;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\CellIterator;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

class WipImportService
{
  use ParseDateTrait;
  use Sanitize;
  protected $packageCapacityService;
  protected $f1f2WipRepository;
  protected $importTraceRepository;
  protected $f1f2WipOutRepository;
  protected $f3WipRepository;
  protected $pickUpRepository;
  protected $f3OutRepository;
  protected $fileValidator;
  protected $f3RawPackageRepository;
  protected $csvReader;
  protected $flags = IReader::READ_DATA_ONLY | IReader::IGNORE_ROWS_WITH_NO_CELLS;
  protected $emptyCellFlags = CellIterator::TREAT_EMPTY_STRING_AS_EMPTY_CELL;
  protected string $WIP_QUANTITY_EXCEL_PATH = '\\\\192.168.1.13\\ftproot\\daily_backend_wip.csv';

  // -------------------- for test only --------------------
  // protected string $WIP_QUANTITY_EXCEL_PATH = "C:\\Users\\telford.prog_trainee\\Downloads\\daily_backend_wip.csv";

  protected string $CAPACITY_EXCEL_PATH = "C:\\Users\\telford.prog_trainee\\Downloads\\TSPI Capacity.xlsx";
  protected string $F3_WIP_PATH = "C:\\Users\\telford.prog_trainee\\Downloads\\F3 WIP Sample.xlsx";
  // -------------------- for test only --------------------

  protected string $WIP_OUTS_EXCEL_PATH = "\\\\192.168.1.13\\ftproot\\daily_telford_outs.csv";
  // protected string $WIP_OUTS_EXCEL_PATH = "C:\\Users\\telford.prog_trainee\\Downloads\\daily_telford_outs.csv";
  private const WIP_QUANTITY_LOCK_KEY = 'auto_import_wip_lock';
  private const DAILY_BACKEND_WIP_DATETIME_FORMAT = 'd/m/Y H:i';
  private const WIP_OUTS_LOCK_KEY = 'auto_import_wip_outs_lock';
  private const EXPECTED_DAILY_BACKEND_WIP_COLUMNS = 43;
  private const CHUNK_SIZE = 500;

  protected $sheetToArrayArgs = [
    null,  // nullValue
    true,  // calculateFormulas
    false, // formatData
    false, // returnCellRef
    true,  // preserveEmptyRows
    false, // strictNullComparison
  ];

  public function __construct(
    F1F2WipRepository $f1f2WipRepository,
    F1F2OutRepository $f1f2WipOutRepository,
    PickUpRepository $pickUpRepository,
    F3WipRepository $f3WipRepository,
    F3OutRepository $f3OutRepository,
    F3RawPackageRepository $f3RawPackageRepository,
    ImportTraceRepository $importTraceRepository,
    PackageCapacityService $packageCapacityService,
    ExcelValidatorService $fileValidator
  ) {
    $this->pickUpRepository = $pickUpRepository;
    $this->f1f2WipRepository = $f1f2WipRepository;
    $this->f1f2WipOutRepository = $f1f2WipOutRepository;
    $this->f3WipRepository = $f3WipRepository;
    $this->f3OutRepository = $f3OutRepository;
    $this->f3RawPackageRepository = $f3RawPackageRepository;
    $this->importTraceRepository = $importTraceRepository;
    $this->packageCapacityService = $packageCapacityService;
    $this->fileValidator = $fileValidator;

    $this->csvReader = new Csv();
  }

  private function insertChunk(
    array $chunk,
    callable $operation,
    int &$successCounter
  ): void {
    if (empty($chunk)) return;

    DB::transaction(fn() => $operation($chunk));

    $successCounter += count($chunk);
  }

  private function getSanitizedSheetData($spreadsheet)
  {
    $sheet = $spreadsheet->getActiveSheet();
    $sheetData = $sheet->toArray(...$this->sheetToArrayArgs);

    return $sheetData = array_map(
      fn($row) => array_map([$this, 'sanitizeExcelCell'], $row),
      $sheetData
    );
  }


  protected function rowFormatterWIP(array $rowData): array
  {
    $rowData['Date_Loaded'] = $this->parseDate($rowData['Date_Loaded'], null);
    $rowData['Start_Time'] = $this->parseDate($rowData['Start_Time'], null);
    $rowData['Reqd_Time'] = $this->parseDate($rowData['Reqd_Time'], null);
    $rowData['Lot_Entry_Time'] = $this->parseDate($rowData['Lot_Entry_Time'], null);
    $rowData['Stage_Start_Time'] = $this->parseDate($rowData['Stage_Start_Time'], null);
    $rowData['CCD'] = $this->parseDate($rowData['CCD'], null);
    $rowData['BE_Starttime'] = $this->parseDate($rowData['BE_Starttime'], null);
    $rowData['Auto_Part'] = strtoupper(trim($rowData['Auto_Part']));
    $rowData['OSL_Days'] = $rowData['OSL_Days'] === '' ? null : (int)$rowData['OSL_Days'];
    $rowData['BE_OSL_Days'] = $rowData['BE_OSL_Days'] === '' ? null : (int)$rowData['BE_OSL_Days'];
    $rowData['Backend_Leadtime'] = $rowData['Backend_Leadtime'] === '' ? null : (int)$rowData['Backend_Leadtime'];
    $rowData['Lead_Count'] = $rowData['Lead_Count'] === '' ? null : (int)$rowData['Lead_Count'];
    $rowData['Bake_Count'] = $rowData['Bake_Count'] === '' ? null : (int)$rowData['Bake_Count'];
    $rowData['Stage_Run_Days'] = $rowData['Stage_Run_Days'] === '' ? null : (int)$rowData['Stage_Run_Days'];
    $rowData['Lot_Entry_Time_Days'] = $rowData['Lot_Entry_Time_Days'] === '' ? null : (int)$rowData['Lot_Entry_Time_Days'];
    $rowData['Qty'] = $rowData['Qty'] === '' ? null : (int)$rowData['Qty'];
    return $rowData;
  }

  protected function rowFormatterOUT(array $rowData): array
  {
    $rowData['date_loaded'] = $this->parseDate($rowData['date_loaded'], null);
    $rowData['package'] = strtoupper((string) ($rowData['package'] ?? ''));
    $rowData['out_date'] = $this->parseDate($rowData['out_date'], null);
    $rowData['test_lot_id'] = (string) ($rowData['test_lot_id'] ?? '');
    $rowData['qty'] = $rowData['qty'] === '' ? null : (int)$rowData['qty'];
    return $rowData;
  }

  public function ftpRootImportF1F2WIP($importedBy = null): array
  {
    date_default_timezone_set('Asia/Manila');
    if (!file_exists($this->WIP_QUANTITY_EXCEL_PATH)) {
      return ['status' => 'error', 'message' => 'CSV file not found or inaccessible.'];
    }

    $tempFile = sys_get_temp_dir() . '\\daily_backend_wip.csv';
    copy($this->WIP_QUANTITY_EXCEL_PATH, $tempFile);

    $reader = ReaderEntityFactory::createCSVReader();
    $reader->open($tempFile);

    return $this->processCsvImport($tempFile, $reader, $importedBy, WipConstants::IMPORT_MANUAL_F1F2_WIP_EXPECTED_HEADERS, $this->f1f2WipRepository, self::WIP_QUANTITY_LOCK_KEY, [$this, 'rowFormatterWIP']);
  }

  public function importF1F2WIP($importedBy = null, $file, $extension = '.csv')
  {
    date_default_timezone_set('Asia/Manila');
    $path = $file->getPathname();
    $tmpPath = $path . $extension;
    rename($path, $tmpPath);

    $reader = ReaderEntityFactory::createCSVReader();
    $reader->open($tmpPath);

    return $this->processCsvImport($tmpPath, $reader, $importedBy, WipConstants::IMPORT_MANUAL_F1F2_WIP_EXPECTED_HEADERS, $this->f1f2WipRepository, self::WIP_QUANTITY_LOCK_KEY, [$this, 'rowFormatterWIP']);
  }

  public function ftpRootImportF1F2OUT($importedBy = null): array
  {
    date_default_timezone_set('Asia/Manila');
    if (!file_exists($this->WIP_OUTS_EXCEL_PATH)) {
      return ['status' => 'error', 'message' => 'CSV file not found or inaccessible.'];
    }

    $tempFile = sys_get_temp_dir() . '\\daily_backend_out.csv';
    copy($this->WIP_OUTS_EXCEL_PATH, $tempFile);

    $reader = ReaderEntityFactory::createCSVReader();
    $reader->open($tempFile);

    return $this->processCsvImport($tempFile, $reader, $importedBy, WipConstants::IMPORT_MANUAL_F1F2_OUT_EXPECTED_HEADERS, $this->f1f2WipOutRepository, self::WIP_OUTS_LOCK_KEY, [$this, 'rowFormatterOUT']);
  }


  public function importF1F2OUT($importedBy = null, $file, $extension = '.csv')
  {
    date_default_timezone_set('Asia/Manila');
    $path = $file->getPathname();
    $tmpPath = $path . $extension;
    rename($path, $tmpPath);

    $reader = ReaderEntityFactory::createCSVReader();
    $reader->open($tmpPath);

    return $this->processCsvImport($tmpPath, $reader, $importedBy, WipConstants::IMPORT_MANUAL_F1F2_OUT_EXPECTED_HEADERS, $this->f1f2WipOutRepository, self::WIP_OUTS_LOCK_KEY, [$this, 'rowFormatterOUT']);
  }

  private function processCsvImport(
    string $filePath,
    $reader,
    $importedBy,
    array $expectedHeaders,
    $repository,
    string $lockKey,
    callable $rowFormatter
  ): array {
    $headersData = $this->fileValidator->getExcelCanonicalHeaderSpout($filePath, $expectedHeaders);

    if ($headersData['status'] === 'error') {
      return $headersData;
    }

    $found_headers = $headersData['found_headers'];
    $headerRowIndex = $headersData['headerRowIndex'];
    $map_headers = $headersData['map_headers'];

    $successCount = 0;
    $chunk = [];

    $lock = Cache::lock($lockKey, 100);
    if (!$lock->get()) {
      throw new Exception('The import is currently running. Try again in a few minutes.');
    }

    try {
      DB::beginTransaction();

      $reader->open($filePath);
      $currentRowIndex = 0;

      $repository->deleteTodayRecords();

      foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
          $currentRowIndex++;
          if ($currentRowIndex <= $headerRowIndex) continue;

          $rowCells = $row->toArray();
          if ($this->isEmptyRow($rowCells)) continue;

          $rowData = $this->extractRowData($map_headers, $rowCells, $found_headers);
          $rowData['imported_by'] = $importedBy;

          // custom formatting per type
          $rowData = $rowFormatter($rowData);

          $chunk[] = $rowData;

          if (count($chunk) >= self::CHUNK_SIZE) {
            $this->insertChunk($chunk, fn($c) => $repository->insertManyCustomers($c), $successCount);
            $chunk = [];
          }
        }
        break;
      }

      if (!empty($chunk)) {
        $this->insertChunk($chunk, fn($c) => $repository->insertManyCustomers($c), $successCount);
      }

      $this->importTraceRepository->upsertImport($repository->getImportKey(), $importedBy, $successCount);
      Cache::forget(WipConstants::TODAY_WIP_CACHE_KEY);

      $reader->close();
      unlink($filePath);

      DB::commit();

      return [
        'status' => 'success',
        'message' => $successCount === 0 ? 'No new records found.' : 'Import completed successfully.',
        'data' => [$repository->getImportKey() => $successCount],
        'total' => $successCount
      ];
    } catch (Exception $e) {
      DB::rollBack();

      Log::error('Import failed', ['error' => $e->getMessage()]);
      return ['status' => 'error', 'message' => $e->getMessage()];
    } finally {
      $lock->release();
    }
  }

  private function prepareExistingF3WipRecords(): array
  {
    $records = $this->f3WipRepository->getExistingRecords();
    $map = [];

    foreach ($records as $r) {
      $map["{$r->lot_number}-{$r->date_loaded}"] = true;
    }

    return $map;
  }

  private function prepareExistingF3OutRecords(): array
  {
    $records = $this->f3OutRepository->getExistingRecords();
    $map = [];

    foreach ($records as $r) {
      $map["{$r->lot_number}-{$r->date_loaded}"] = true;
    }

    return $map;
  }

  public function importCapacity($importedBy = null, $file)
  {
    $spreadsheet = IOFactory::load($file->getPathname(), $this->flags);

    $sheet = $spreadsheet->getActiveSheet();

    $data = [];
    $currentFactory = null;
    $rowIterator = $sheet->getRowIterator();

    $mergedCells = $sheet->getMergeCells();
    // Log::info('Merged cells: ' . print_r($mergedCells, true));
    $lastPackage = null;
    foreach ($rowIterator as $row) {
      $pos = $row->getRowIndex();

      $colA = trim($sheet->getCell([1, $pos])->getCalculatedValue());
      $colD = trim($sheet->getCell([4, $pos])->getCalculatedValue());

      // Log::info("pos: $pos, colA: $colA, colD: $colD");

      if (preg_match('/^F\d+/i', $colA)) {
        $currentFactory = $colA;
        continue;
      }

      if (preg_match('/Updated.*Capacity|Capacity.*Updated/i', $colA)) {
        continue;
      }

      if ($colA == 'Total' || $colA == 'Overall Total') {
        continue;
      }

      if (!empty($colA)) {
        // TODO might have separator for ColA
        $key = $colA . '|' . $currentFactory;

        $data[$key] = [
          'package_name' => $colA,
          'factory_name' => $currentFactory,
          'effective_from' => date('Y-m-d', strtotime('today')),
          // 'effective_from' => date('Y-m-d', strtotime('-10 day')),
          'capacity' => (int)$colD,
        ];

        $lastPackage = $key;
      } else {
        if ($lastPackage !== null) {
          $data[$lastPackage]['capacity'] += (int)$colD;
        }
      }
    }

    $total = count($data);

    // Log::info((
    //   "data: " . print_r($data, true)
    // ));

    $return = $this->packageCapacityService->upsertMultiple($data);
    $this->importTraceRepository->upsertImport('capacity', $importedBy, $total);

    return [
      'status' => 'success',
      'message' => 'Capacity Update completed',
      'data' => array_values($data),
      'updated' => $return['updated'],
      'created' => $return['created'],
      'total' => $total
    ];
  }

  protected function isEmptyRow(array $row): bool
  {
    return count(array_filter($row, fn($value) => $value !== null && $value !== '')) === 0;
  }

  protected function extractRowData(array $map, array $row, array $headers): array
  {
    $rowData = [];

    foreach ($map as $i => $colIndex) {
      $key = $headers[$i];
      $rowData[$key] = $row[$colIndex] ?? null;
    }

    return $rowData;
  }

  public function importF1F2PickUp($importedBy = null, $file)
  {
    $spreadsheet = IOFactory::load($file->getPathname(), $this->flags);
    $sheetData = $this->getSanitizedSheetData($spreadsheet);

    $headersData = $this->fileValidator->getExcelCanonicalHeader($spreadsheet, WipConstants::IMPORT_F1F2_PICKUP_EXPECTED_HEADERS);

    if ($headersData['status'] === 'error') {
      return $headersData;
    }

    $found_headers = $headersData['found_headers'];
    $headerRowIndex = $headersData['headerRowIndex'];
    $map_headers = $headersData['map_headers'];

    $chunks = [];
    $successCount = 0;

    try {
      DB::beginTransaction();

      foreach (array_slice($sheetData, $headerRowIndex) as $rowIndex => $row) {
        if ($this->isEmptyRow($row)) {
          continue;
        }

        $rowData = $this->extractRowData($map_headers, $row, $found_headers);

        $rowData['ADDED_BY'] = $importedBy;

        $chunks[] = $rowData;

        if (count($chunks) >= self::CHUNK_SIZE) {
          $this->insertChunk($chunks, fn($chunks) => $this->pickUpRepository->insertMany($chunks), $successCount);
          $chunks = [];
        }
      }

      if (!empty($chunks)) {
        $this->insertChunk($chunks, fn($chunks) => $this->pickUpRepository->insertMany($chunks), $successCount);
      }

      $this->importTraceRepository->upsertImport('pickup', $importedBy, $successCount);

      DB::commit();

      return [
        'status' => 'success',
        'message' =>  $successCount === 0 ? 'No new records found.' : 'Import completed successfully.',
        'data' => [
          'total' => $successCount,
        ]
      ];
    } catch (Exception $e) {
      DB::rollBack();

      return ['status' => 'error', 'message' => $e->getMessage()];
    }
  }

  public function importF3PickUp($importedBy = null, $file)
  {
    $spreadsheet = IOFactory::load($file->getPathname(), $this->flags);
    $sheetData = $this->getSanitizedSheetData($spreadsheet);

    $headersData = $this->fileValidator->getExcelCanonicalHeader($spreadsheet, WipConstants::IMPORT_F3_PICKUP_EXPECTED_HEADERS);

    if ($headersData['status'] === 'error') {
      return $headersData;
    }

    $found_headers = $headersData['found_headers'];
    $headerRowIndex = $headersData['headerRowIndex'];
    $map_headers = $headersData['map_headers'];

    $chunks = [];
    $ignoredRows = [];
    $successCount = 0;

    try {
      DB::beginTransaction();

      foreach (array_slice($sheetData, $headerRowIndex) as $rowIndex => $row) {
        if ($this->isEmptyRow($row)) {
          continue;
        }

        $rowData = $this->extractRowData($map_headers, $row, $found_headers);

        $rowData['ADDED_BY'] = $importedBy;

        $f3RawPackage = $this->f3RawPackageRepository->getByRawPackage($rowData['PACKAGE'] ?? null);
        if (!$f3RawPackage) {
          $ignoredRows[] = $rowData;
          continue;
        }

        $rowData['PACKAGE'] = $f3RawPackage->package_name;
        $rowData['LC'] = $f3RawPackage->lead_count;
        $chunks[] = $rowData;

        if (count($chunks) >= self::CHUNK_SIZE) {
          $this->insertChunk($chunks, fn($chunks) => $this->pickUpRepository->insertMany($chunks), $successCount);
          $chunks = [];
        }
      }

      if (!empty($chunks)) {
        $this->insertChunk($chunks, fn($chunks) => $this->pickUpRepository->insertMany($chunks), $successCount);
      }

      $this->importTraceRepository->upsertImport('f3_pickup', $importedBy, $successCount);

      DB::commit();

      return [
        'status' => 'success',
        'message' =>  $successCount === 0 ? 'No new records found.' : 'Import completed successfully.',
        'data' => [
          'total' => $successCount,
          'ignored_unknown_package' => array_slice($ignoredRows, 0, 100),
          'ignored_unknown_package_count' => count($ignoredRows),
        ]
      ];
    } catch (Exception $e) {
      DB::rollBack();

      return ['status' => 'error', 'message' => $e->getMessage()];
    }
  }

  public function importF3WIP($importedBy = null, $file)
  {
    $spreadsheet = IOFactory::load($file->getPathname(), $this->flags);
    $sheetData = $this->getSanitizedSheetData($spreadsheet);

    $headersData = $this->fileValidator->getExcelCanonicalHeader($spreadsheet, WipConstants::IMPORT_F3_WIP_EXPECTED_HEADERS);

    if ($headersData['status'] === 'error') {
      return $headersData;
    }

    $found_headers = $headersData['found_headers'];
    $headerRowIndex = $headersData['headerRowIndex'];
    $map_headers = $headersData['map_headers'];

    $chunks = [];
    $ignoredRows = [];
    $successCount = 0;
    // Log::info("headerRowIndex: " . print_r($headerRowIndex, true));
    $existingRecords = $this->prepareExistingF3WipRecords();

    try {
      DB::beginTransaction();

      foreach (array_slice($sheetData, $headerRowIndex) as $rowIndex => $row) {
        if ($this->isEmptyRow($row)) {
          continue;
        }

        $rowData = $this->extractRowData($map_headers, $row, $found_headers);

        $rowData['imported_by'] = $importedBy;
        $rowData['date_received'] = $this->parseDate($rowData['date_received'], null);
        $rowData['date_loaded'] = $this->parseDate($rowData['date_loaded'], null);
        $rowData['actual_date_time'] = $this->parseDate($rowData['actual_date_time'], null);
        $rowData['date_commit'] = $this->parseDate($rowData['date_commit'], null);

        $packageID = $this->f3RawPackageRepository->getIDByRawPackage($rowData['package']);

        if (!$packageID) {
          // Log::info("Package not found: " . $rowData['package']);
          $ignoredRows[] = $rowData;
          continue;
        }

        $rowData['package'] = $packageID;

        // Log::info("Processing row {$rowIndex}" . print_r($rowData, true));
        // Log::info("Processing row {$rowIndex}" . print_r($rowData, true));

        $key = "{$rowData['lot_number']}-{$rowData['date_loaded']}";

        // Log::info("Key: " . $key);

        if (isset($existingRecords[$key])) {
          // Log::info("Skipping existing record: " . print_r($rowData, true));
          continue;
        }

        $chunks[] = $rowData;

        if (count($chunks) >= self::CHUNK_SIZE) {
          $this->insertChunk($chunks, fn($chunks) => $this->f3WipRepository->insertManyF3($chunks), $successCount);
          $chunks = [];
        }
      }

      if (!empty($chunks)) {
        $this->insertChunk($chunks, fn($chunks) => $this->f3WipRepository->insertManyF3($chunks), $successCount);
      }
      // Log::info("Ignored rows: " . print_r($ignoredRows, true));
      // Log::info($this->F3_WIP_PATH);
      // Log::info('F3 Headers: ' . print_r($headers, true));

      $this->importTraceRepository->upsertImport('f3_wip', $importedBy, $successCount);

      DB::commit();
      return [
        'status' => 'success',
        'message' =>  $successCount === 0 ? 'No new records found.' : 'Import completed successfully.',
        'data' => [
          'total' => $successCount,
          // 'ignored' => $ignoredRows,
          'ignored_unknown_package' => array_slice($ignoredRows, 0, 100),
          'ignored_unknown_package_count' => count($ignoredRows)
        ]
      ];
    } catch (Exception $e) {
      DB::rollBack();
      return ['status' => 'error', 'message' => $e->getMessage()];
    }
  }


  public function importF3OUT($importedBy = null, $file)
  {
    $spreadsheet = IOFactory::load($file->getPathname(), $this->flags);
    $sheetData = $this->getSanitizedSheetData($spreadsheet);

    $headersData = $this->fileValidator->getExcelCanonicalHeader($spreadsheet, WipConstants::IMPORT_F3_OUT_EXPECTED_HEADERS);

    if ($headersData['status'] === 'error') {
      return $headersData;
    }

    $found_headers = $headersData['found_headers'];
    $headerRowIndex = $headersData['headerRowIndex'];
    $map_headers = $headersData['map_headers'];
    $chunks = [];

    $ignoredRows = [];
    $successCount = 0;
    // Log::info("headerRowIndex: " . print_r($headerRowIndex, true));
    $existingRecords = $this->prepareExistingF3OutRecords();

    try {
      DB::beginTransaction();
      foreach (array_slice($sheetData, $headerRowIndex) as $rowIndex => $row) {
        if ($this->isEmptyRow($row)) {
          continue;
        }

        $rowData = $this->extractRowData($map_headers, $row, $found_headers);

        // Log::info("Processing row {$rowIndex}" . print_r($rowData, true));

        $rowData['imported_by'] = $importedBy;
        $rowData['date_received'] = $this->parseDate($rowData['date_received'], null);
        $rowData['date_loaded'] = $this->parseDate($rowData['date_loaded'], null);
        $rowData['actual_date_time'] = $this->parseDate($rowData['actual_date_time'], null);
        $rowData['date_commit'] = $this->parseDate($rowData['date_commit'], null);

        $packageID = $this->f3RawPackageRepository->getIDByRawPackage($rowData['package']);

        if (!$packageID) {
          // Log::info("Package not found: " . $rowData['package']);
          $ignoredRows[] = $rowData;
          continue;
        }

        $rowData['package'] = $packageID;

        if (isset($existingRecords["{$rowData['lot_number']}-{$rowData['date_loaded']}"])) {
          continue;
        }

        $chunks[] = $rowData;

        if (count($chunks) >= self::CHUNK_SIZE) {
          $resultError = $this->insertChunk($chunks, fn($chunks) => $this->f3OutRepository->insertManyCustomer($chunks), $successCount);
          $chunks = [];
        }
      }

      if (!empty($chunks)) {
        $resultError = $this->insertChunk($chunks, fn($chunks) => $this->f3OutRepository->insertManyCustomer($chunks), $successCount);
      }
      // Log::info("Ignored rows: " . print_r($ignoredRows, true));
      // Log::info($this->F3_WIP_PATH);
      // Log::info('F3 Headers: ' . print_r($headers, true));

      $this->importTraceRepository->upsertImport('f3_out', $importedBy, $successCount);

      DB::commit();
      return [
        'status' => 'success',
        'message' =>  $successCount === 0 ? 'No new records found.' : 'Import completed successfully.',
        'data' => [
          'total' => $successCount,
          // 'ignored' => $ignoredRows,
          'ignored_unknown_package' => array_slice($ignoredRows, 0, 100),
          'ignored_unknown_package_count' => count($ignoredRows)
        ]
      ];
    } catch (Exception $e) {
      DB::rollBack();
      return ['status' => 'error', 'message' => $e->getMessage()];
    }
  }

  public function importF3($importedBy = null, $file)
  {
    $spreadsheet = IOFactory::load($file->getPathname(), $this->flags);
    $sheetData = $this->getSanitizedSheetData($spreadsheet);

    $expectedHeaders = WipConstants::IMPORT_F3_WIP_EXPECTED_HEADERS;
    $headersData = $this->fileValidator->getExcelCanonicalHeader($spreadsheet, $expectedHeaders);
    if ($headersData['status'] === 'error') {
      return $headersData;
    }

    $found_headers = $headersData['found_headers'];
    $headerRowIndex = $headersData['headerRowIndex'];
    $map_headers = $headersData['map_headers'];

    $chunksWip = [];
    $ignoredRows = [];
    $successCount = 0;

    try {
      DB::beginTransaction();

      $this->f3WipRepository->deleteTodayRecords();

      foreach (array_slice($sheetData, $headerRowIndex) as $rowIndex => $row) {
        if ($this->isEmptyRow($row)) {
          continue;
        }

        $rowData = $this->extractRowData($map_headers, $row, $found_headers);

        $rowData['imported_by'] = $importedBy;
        $rowData['date_received'] = $this->parseDate($rowData['date_received'] ?? null);
        $rowData['date_loaded'] = $this->parseDate($rowData['date_loaded'] ?? null);
        $rowData['actual_date_time'] = $this->parseDate($rowData['actual_date_time'] ?? null);
        $rowData['date_commit'] = $this->parseDate($rowData['date_commit'] ?? null);

        $rowData['doable'] = $this->sanitizeInteger($rowData['doable'] ?? null);
        $rowData['qty'] = $this->sanitizeInteger($rowData['qty'] ?? null);
        $rowData['good'] = $this->sanitizeInteger($rowData['good'] ?? null);
        $rowData['rej'] = $this->sanitizeInteger($rowData['rej'] ?? null);
        $rowData['res'] = $this->sanitizeInteger($rowData['res'] ?? null);

        $packageID = $this->f3RawPackageRepository->getIDByRawPackage($rowData['package'] ?? null);
        if (!$packageID) {
          $ignoredRows[] = $rowData;
          continue;
        }
        $rowData['package'] = $packageID;
        $chunksWip[] = $rowData;

        if (count($chunksWip) >= self::CHUNK_SIZE) {
          $this->insertChunk(
            $chunksWip,
            fn($chunks) => $this->f3WipRepository->insertManyF3($chunks),
            $successCount
          );
          $chunksWip = [];
        }
      }

      // Insert remaining rows
      if (!empty($chunksWip)) {
        $this->insertChunk(
          $chunksWip,
          fn($chunks) => $this->f3WipRepository->insertManyF3($chunks),
          $successCount
        );
      }

      $this->importTraceRepository->upsertImport('f3', $importedBy, $successCount);

      DB::commit();

      return [
        'status' => 'success',
        'message' => $successCount === 0 ? 'No new records found.' : 'Import completed successfully.',
        'data' => [
          'total' => $successCount,
          'ignored_unknown_package' => array_slice($ignoredRows, 0, 100),
          'ignored_unknown_package_count' => count($ignoredRows),
        ]
      ];
    } catch (\Throwable $e) {
      DB::rollBack();
      // Optionally log the error
      Log::error('Import failed: ' . $e->getMessage());
      return [
        'status' => 'error',
        'message' => 'Import failed. Transaction rolled back.',
        'error' => $e->getMessage()
      ];
    }
  }
}
