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
use Carbon\Carbon;
use App\Models\CustomerDataWip;
use App\Models\F1F2Out;
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
  ): ?array {
    if (empty($chunk)) return null;

    try {
      DB::transaction(fn() => $operation($chunk));
      $successCounter += count($chunk);
      return null;
    } catch (Exception $e) {
      return [
        'status' => 'error',
        'message' => 'Import Interrupted: ' . $e->getMessage(),
        'data' => [
          'partialSuccess' => $successCounter,
        ]
      ];
    }
  }

  public function ftpRootImportF1F2WIP($importedBy = null): array
  {
    date_default_timezone_set('Asia/Manila');

    if (!file_exists($this->WIP_QUANTITY_EXCEL_PATH)) {
      return ['status' => 'error', 'message' => 'CSV file not found or inaccessible.'];
    }

    $lock = Cache::lock(self::WIP_QUANTITY_LOCK_KEY, 100);

    if (!$lock->get()) {
      throw new Exception('The WIP import is currently running. To prevent duplicate processing, you can try again after a few minutes (up to 10 minutes).');
    }

    try {
      $customerChunk = [];
      $f3Chunk = [];
      $successCustomer = 0;
      $successF3 = 0;

      if (($handle = fopen($this->WIP_QUANTITY_EXCEL_PATH, 'r')) === false) {
        throw new Exception('Failed to open the CSV file.');
      }

      fgetcsv($handle, 0, ','); // skip header
      $firstRow = fgetcsv($handle);
      if (!$firstRow) {
        throw new Exception("CSV contains no data rows.");
      }

      CustomerDataWip::where('import_date', Carbon::today())->delete();

      rewind($handle);
      fgetcsv($handle, 0, ','); // skip header

      while (($row = fgetcsv($handle, 0, ',')) !== false) {

        if (count($row) < self::EXPECTED_DAILY_BACKEND_WIP_COLUMNS) {
          continue;
        }

        Log::info("row: " . print_r($row, true));

        $row = $this->sanitizeRow($row, $importedBy);
        $focusGroup = $row['Focus_Group'];

        if ($focusGroup === 'F3') {
          $f3Chunk[] = $row;
        } else {
          $customerChunk[] = $row;
        }

        if (count($f3Chunk) >= self::CHUNK_SIZE) {
          $result = $this->insertChunk($f3Chunk, fn($f3Chunk) => $this->f1f2WipRepository->insertManyCustomers($f3Chunk), $successF3);

          if ($result) return $result;

          $f3Chunk = [];
        }

        if (count($customerChunk) >= self::CHUNK_SIZE) {
          $result = $this->insertChunk($customerChunk, fn($customerChunk) => $this->f1f2WipRepository->insertManyCustomers($customerChunk), $successCustomer);

          if ($result) return $result;

          $customerChunk = [];
        }
      }

      foreach (['F3' => $f3Chunk, 'F1F2' => $customerChunk] as $type => $chunk) {
        $counter = $type === 'F3' ? $successF3 : $successCustomer;
        $result = $this->insertChunk($chunk, fn($f3Chunk) => $this->f1f2WipRepository->insertManyCustomers($f3Chunk), $counter);

        if ($result) return $result;
      }

      fclose($handle);

      $this->importTraceRepository->upsertImport('f1f2_wip', $importedBy, $successCustomer + $successF3);
      Cache::forget(WipConstants::TODAY_WIP_CACHE_KEY);

      return [
        'status' => 'success',
        'message' =>  $successCustomer + $successF3 === 0 ? 'No new records found.' : 'Import completed successfully.',
        'data' => [
          'f1f2' => $successCustomer,
          'f3' => $successF3
        ],
        'total' => $successF3 + $successCustomer
      ];
    } catch (Exception $e) {
      Log::error('WIP import failed', ['error' => $e->getMessage()]);
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

    // Log::info("prepareExistingF3WipRecords: " . print_r($map, true));

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

  private function sanitizeRow(array $row, $importedBy): array
  {
    $row = array_map(fn($v) => trim($v) === '' ? null : trim($v), $row);

    // TODO: what to do with missing values???
    // TOOD: OK response from server error ?????? 

    $now = Carbon::now();
    // Log::info("row 10: " . $row[10]);
    $row[10] = $this->parseDate($row[10], $now);
    $row[11] = $this->parseDate($row[11]);
    $row[18] = $this->parseDate($row[18]);
    $row[19] = $this->parseDate($row[19]);
    $row[21] = $this->parseDate($row[21]);
    $row[22] = $this->parseDate($row[22]);
    $row[31] = $this->parseDate($row[31]);
    $row[33] = strtoupper(trim($row[31]));

    $keys = F1F2WipRepository::getKeys();

    $values = array_pad($row, count($keys) - 1, null); // pad row to 43 elements
    $values[] = $importedBy;
    return array_combine($keys, $values);
  }

  public function csvLoad($file)
  {
    try {
      $this->csvReader->setDelimiter(',');
      $this->csvReader->setEnclosure('"');
      $this->csvReader->setInputEncoding('UTF-8');
      $this->csvReader->setSheetIndex(0);

      $content = file_get_contents(filename: $file);
      $spreadsheet = $this->csvReader->loadSpreadsheetFromString($content);

      // Log::info('Spreadsheet loaded successfully.' . $content);

      return $spreadsheet;
    } catch (Exception $e) {
      throw new \RuntimeException('Failed to load file: ' . $e->getMessage());
    }
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

      $colA = trim($sheet->getCell('A' . $pos)->getCalculatedValue());
      $colD = trim($sheet->getCell('D' . $pos)->getCalculatedValue());

      if (preg_match('/^F\d+/i', $colA)) {
        $currentFactory = $colA;
        continue;
      }

      if (preg_match('/Updated.*Capacity|Capacity.*Updated/i', $colA)) {
        continue;
      }

      // Log::info('Row ' . $pos . ': ' . $colA . ', ' . $colD);

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

  public function ftpRootImportF1F2Outs($importedBy = null)
  {
    $spreadsheet = $this->csvLoad($this->WIP_OUTS_EXCEL_PATH);
    $sheet = $spreadsheet->getActiveSheet();
    $sheetData = $sheet->toArray(null, true, false, false);


    $headersData = $this->fileValidator->getExcelCanonicalHeader($spreadsheet, WipConstants::IMPORT_WIP_OUTS_EXPECTED_HEADERS);

    if ($headersData['status'] === 'error') {
      return $headersData;
    }

    $headers = $headersData['found_headers'];
    $headerRowIndex = $headersData['headerRowIndex'];
    $lock = Cache::lock(self::WIP_OUTS_LOCK_KEY, 600);

    $successCount = 0;
    $preparedRows = [];

    $firstRow = $sheetData[$headerRowIndex] ?? null;
    if (!$firstRow) {
      return ['status' => 'error', 'message' => 'No data found.'];
    }

    if (!$lock->get()) {
      throw new Exception('The OUT import is currently running. To prevent duplicate processing, you can try again after a few minutes (up to 10 minutes).');
    }

    $headers = $headersData['found_headers'];

    F1F2Out::where('import_date', Carbon::today())->delete();

    foreach (array_slice($sheetData, $headerRowIndex) as $rowIndex => $row) {
      $rowData = [];

      foreach ($headers as $colIndex => $canonicalKey) {
        $value = $row[$colIndex] ?? null;

        if ($value === '') {
          $value = null;
        }

        $rowData[$canonicalKey] = $value;
      }

      if (isset($rowData['package'])) {
        $rowData['package'] = strtoupper((string) ($rowData['package'] ?? ''));
      }
      if (isset($rowData['qty'])) {
        $rowData['qty'] = (int) $rowData['qty'];
      }
      if (isset($rowData['out_date']) && $rowData['out_date']) {
        $rowData['out_date'] = Carbon::parse($rowData['out_date']);
      }
      if (isset($rowData['date_loaded']) && $rowData['date_loaded']) {
        $rowData['date_loaded'] = Carbon::parse($rowData['date_loaded']);
      }
      if (isset($rowData['test_lot_id']) && $rowData['test_lot_id']) {
        $rowData['test_lot_id'] = (string) ($rowData['test_lot_id'] ?? '');
      }

      $rowData['imported_by'] = $importedBy;

      $preparedRows[] = $rowData;

      if (count($preparedRows) >= self::CHUNK_SIZE) {
        $result = $this->insertChunk($preparedRows, fn($preparedRows) => $this->f1f2WipOutRepository->insertManyCustomers($preparedRows), $successCount);

        if ($result) return $result;

        $preparedRows = [];
      }
    }

    if (count($preparedRows) > 0) {
      $result = $this->insertChunk($preparedRows, fn($preparedRows) => $this->f1f2WipOutRepository->insertManyCustomers($preparedRows), $successCount);

      if ($result) return $result;
    }

    $this->importTraceRepository->upsertImport('f1f2_out', $importedBy, $successCount);

    $lock->release();

    return [
      'status' => 'success',
      'message' => $successCount > 0 ? 'Records imported successfully.' : 'No new records found.',
      'total' => $successCount
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

  public function importPickUp($importedBy = null, $file)
  {
    $spreadsheet = IOFactory::load($file->getPathname(), $this->flags);

    $sheet = $spreadsheet->getActiveSheet();
    $sheetData = $sheet->toArray(null, true, false, false);

    $headersData = $this->fileValidator->getExcelCanonicalHeader($spreadsheet, WipConstants::IMPORT_PICKUP_EXPECTED_HEADERS);

    if ($headersData['status'] === 'error') {
      return $headersData;
    }

    $found_headers = $headersData['found_headers'];
    $headerRowIndex = $headersData['headerRowIndex'];
    $map_headers = $headersData['map_headers'];

    $chunks = [];
    $successCount = 0;

    foreach (array_slice($sheetData, $headerRowIndex) as $rowIndex => $row) {
      if ($this->isEmptyRow($row)) {
        continue;
      }

      $rowData = $this->extractRowData($map_headers, $row, $found_headers);

      $rowData['ADDED_BY'] = $importedBy;

      $chunks[] = $rowData;

      if (count($chunks) >= self::CHUNK_SIZE) {
        $resultError = $this->insertChunk($chunks, fn($chunks) => $this->pickUpRepository->insertMany($chunks), $successCount);

        if ($resultError) return $resultError;
        $chunks = [];
      }
    }

    if (!empty($chunks)) {
      $resultError = $this->insertChunk($chunks, fn($chunks) => $this->pickUpRepository->insertMany($chunks), $successCount);

      if ($resultError) return $resultError;
    }

    $this->importTraceRepository->upsertImport('pickup', $importedBy, $successCount);

    return [
      'status' => 'success',
      'message' =>  $successCount === 0 ? 'No new records found.' : 'Import completed successfully.',
      'data' => [
        'total' => $successCount,
      ]
    ];
  }

  public function importF3WIP($importedBy = null, $file)
  {
    $spreadsheet = IOFactory::load($file->getPathname(), $this->flags);

    $sheet = $spreadsheet->getActiveSheet();
    $sheetData = $sheet->toArray(null, true, false, false);

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
        Log::info("Package not found: " . $rowData['package']);
        $ignoredRows[] = $rowData;
        continue;
      }

      $rowData['package'] = $packageID;

      // Log::info("Processing row {$rowIndex}" . print_r($rowData, true));
      // Log::info("Processing row {$rowIndex}" . print_r($rowData, true));

      $key = "{$rowData['lot_number']}-{$rowData['date_loaded']}";

      // Log::info("Key: " . $key);

      if (isset($existingRecords[$key])) {
        Log::info("Skipping existing record: " . print_r($rowData, true));
        continue;
      }

      $chunks[] = $rowData;

      if (count($chunks) >= self::CHUNK_SIZE) {
        $resultError = $this->insertChunk($chunks, fn($chunks) => $this->f3WipRepository->insertManyF3($chunks), $successCount);

        if ($resultError) return $resultError;
        $chunks = [];
      }
    }

    if (!empty($chunks)) {
      $resultError = $this->insertChunk($chunks, fn($chunks) => $this->f3WipRepository->insertManyF3($chunks), $successCount);

      if ($resultError) return $resultError;
    }
    // Log::info("Ignored rows: " . print_r($ignoredRows, true));
    // Log::info($this->F3_WIP_PATH);
    // Log::info('F3 Headers: ' . print_r($headers, true));

    $this->importTraceRepository->upsertImport('f3_wip', $importedBy, $successCount);

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
  }

  public function importF1F2WIP($importedBy = null, $file, $extension = '.csv')
  {
    date_default_timezone_set('Asia/Manila');

    $path = $file->getPathname();
    $tmpPath = $path . $extension;
    rename($path, $tmpPath);

    $reader = ReaderEntityFactory::createCSVReader();
    $headersData = $this->fileValidator->getExcelCanonicalHeaderSpout($tmpPath, WipConstants::IMPORT_MANUAL_F1F2_WIP_EXPECTED_HEADERS);

    if ($headersData['status'] === 'error') {
      return $headersData;
    }

    $found_headers = $headersData['found_headers'];
    $headerRowIndex = $headersData['headerRowIndex'];
    $map_headers = $headersData['map_headers'];

    $successCustomer = 0;
    $customerChunk = [];

    $lock = Cache::lock(self::WIP_QUANTITY_LOCK_KEY, 100);

    if (!$lock->get()) {
      throw new Exception('The WIP import is currently running. To prevent duplicate processing, you can try again after a few minutes (up to 10 minutes).');
    }

    try {
      $reader = ReaderEntityFactory::createCSVReader();
      $reader->open($tmpPath);
      $currentRowIndex = 0;

      CustomerDataWip::where('import_date', Carbon::today())->delete();

      foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
          $currentRowIndex++;

          if ($currentRowIndex <= $headerRowIndex) {
            continue;
          }

          $rowCells = $row->toArray();

          if ($this->isEmptyRow($rowCells)) {
            continue;
          }

          $rowData = $this->extractRowData($map_headers, $rowCells, $found_headers);

          $rowData['imported_by'] = $importedBy;
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

          $customerChunk[] = $rowData;

          if (count($customerChunk) >= self::CHUNK_SIZE) {
            $result = $this->insertChunk($customerChunk, fn($customerChunk) => $this->f1f2WipRepository->insertManyCustomers($customerChunk), $successCustomer);

            if ($result) return $result;

            $customerChunk = [];
          }

          // Log::info("Processing row {$currentRowIndex}" . print_r($rowData, true));
        }

        break;
      }

      $result = $this->insertChunk($customerChunk, fn($chunk) => $this->f1f2WipRepository->insertManyCustomers($chunk), $successCustomer);
      if ($result) return $result;

      $this->importTraceRepository->upsertImport('f1f2_wip', $importedBy, $successCustomer);
      Cache::forget(WipConstants::TODAY_WIP_CACHE_KEY);

      $reader->close();
      return [
        'status' => 'success',
        'message' =>  $successCustomer === 0 ? 'No new records found.' : 'Import completed successfully.',
        'data' => [
          'f1f2' => $successCustomer,
        ],
        'total' => $successCustomer
      ];
    } catch (Exception $e) {
      Log::error('WIP import failed', ['error' => $e->getMessage()]);
      return ['status' => 'error', 'message' => $e->getMessage()];
    } finally {
      $lock->release();
    }
  }

  public function importF1F2OUTS($importedBy = null, $file, $extension = '.csv')
  {
    date_default_timezone_set('Asia/Manila');

    $path = $file->getPathname();
    $tmpPath = $path . $extension;
    rename($path, $tmpPath);

    $reader = ReaderEntityFactory::createCSVReader();
    $headersData = $this->fileValidator->getExcelCanonicalHeaderSpout($tmpPath, WipConstants::IMPORT_MANUAL_F1F2_OUT_EXPECTED_HEADERS);

    if ($headersData['status'] === 'error') {
      return $headersData;
    }

    $found_headers = $headersData['found_headers'];
    $headerRowIndex = $headersData['headerRowIndex'];
    $map_headers = $headersData['map_headers'];

    $successCustomer = 0;
    $customerChunk = [];

    $lock = Cache::lock(self::WIP_OUTS_LOCK_KEY, 100);

    if (!$lock->get()) {
      throw new Exception('The OUT import is currently running. To prevent duplicate processing, you can try again after a few minutes (up to 10 minutes).');
    }

    try {
      $reader = ReaderEntityFactory::createCSVReader();
      $reader->open($tmpPath);
      $currentRowIndex = 0;

      F1F2Out::where('import_date', Carbon::today())->delete();

      foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
          $currentRowIndex++;

          if ($currentRowIndex <= $headerRowIndex) {
            continue;
          }

          $rowCells = $row->toArray();

          if ($this->isEmptyRow($rowCells)) {
            continue;
          }

          $rowData = $this->extractRowData($map_headers, $rowCells, $found_headers);

          $rowData['imported_by'] = $importedBy;
          $rowData['date_loaded'] = $this->parseDate($rowData['date_loaded'], null);
          $rowData['package'] = strtoupper((string) ($rowData['package'] ?? ''));
          $rowData['out_date'] = $this->parseDate($rowData['out_date'], null);
          $rowData['test_lot_id'] = (string) ($rowData['test_lot_id'] ?? '');
          $rowData['qty'] = $rowData['qty'] === '' ? null : (int)$rowData['qty'];

          $customerChunk[] = $rowData;

          if (count($customerChunk) >= self::CHUNK_SIZE) {
            $result = $this->insertChunk($customerChunk, fn($customerChunk) => $this->f1f2WipOutRepository->insertManyCustomers($customerChunk), $successCustomer);

            if ($result) return $result;

            $customerChunk = [];
          }

          // Log::info("Processing row {$currentRowIndex}" . print_r($rowData, true));
        }

        break;
      }

      if (count($customerChunk) >= 0) {
        $result = $this->insertChunk($customerChunk, fn($chunk) => $this->f1f2WipOutRepository->insertManyCustomers($chunk), $successCustomer);

        if ($result) return $result;
      }

      $this->importTraceRepository->upsertImport('f1f2_out', $importedBy, $successCustomer);
      Cache::forget(WipConstants::TODAY_WIP_CACHE_KEY);

      $reader->close();
      return [
        'status' => 'success',
        'message' =>  $successCustomer === 0 ? 'No new records found.' : 'Import completed successfully.',
        'data' => [
          'f1f2' => $successCustomer,
        ],
        'total' => $successCustomer
      ];
    } catch (Exception $e) {
      Log::error('OUTs import failed', ['error' => $e->getMessage()]);
      return ['status' => 'error', 'message' => $e->getMessage()];
    } finally {
      $lock->release();
    }
  }

  public function importF3OUT($importedBy = null, $file)
  {
    $spreadsheet = IOFactory::load($file->getPathname(), $this->flags);

    $sheet = $spreadsheet->getActiveSheet();
    $sheetData = $sheet->toArray(null, true, false, false);

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
        Log::info("Package not found: " . $rowData['package']);
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

        if ($resultError) return $resultError;
        $chunks = [];
      }
    }

    if (!empty($chunks)) {
      $resultError = $this->insertChunk($chunks, fn($chunks) => $this->f3OutRepository->insertManyCustomer($chunks), $successCount);

      if ($resultError) return $resultError;
    }
    // Log::info("Ignored rows: " . print_r($ignoredRows, true));
    // Log::info($this->F3_WIP_PATH);
    // Log::info('F3 Headers: ' . print_r($headers, true));

    $this->importTraceRepository->upsertImport('f3_out', $importedBy, $successCount);

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
  }

  public function importF3($importedBy = null, $file)
  {
    $spreadsheet = IOFactory::load($file->getPathname(), $this->flags);
    $sheet = $spreadsheet->getActiveSheet();
    $sheetData = $sheet->toArray(null, true, false, false);

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

    $existingWipRecords = $this->prepareExistingF3WipRecords();

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

      $packageID = $this->f3RawPackageRepository->getIDByRawPackage($rowData['package'] ?? null);
      if (!$packageID) {
        Log::info("Package not found: " . ($rowData['package'] ?? 'NULL'));
        $ignoredRows[] = $rowData;
        continue;
      }
      $rowData['package'] = $packageID;

      $key = "{$rowData['lot_number']}-{$rowData['date_loaded']}";

      if (isset($existingWipRecords[$key])) {
        continue; // skip duplicates
      }

      $chunksWip[] = $rowData;

      if (count($chunksWip) >= self::CHUNK_SIZE) {
        $resultError = $this->insertChunk($chunksWip, fn($chunks) => $this->f3WipRepository->insertManyF3($chunks), $successCount);
        if ($resultError) return $resultError;
        $chunksWip = [];
      }
    }

    // Insert remaining rows
    if (!empty($chunksWip)) {
      $resultError = $this->insertChunk($chunksWip, fn($chunks) => $this->f3WipRepository->insertManyF3($chunks), $successCount);
      if ($resultError) return $resultError;
    }

    $this->importTraceRepository->upsertImport('f3', $importedBy, $successCount);

    return [
      'status' => 'success',
      'message' => $successCount === 0 ? 'No new records found.' : 'Import completed successfully.',
      'data' => [
        'total' => $successCount,
        'ignored_unknown_package' => array_slice($ignoredRows, 0, 100),
        'ignored_unknown_package_count' => count($ignoredRows)
      ]
    ];
  }
}
