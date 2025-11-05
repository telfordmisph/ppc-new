<?php

namespace App\Services;

use App\Repositories\WipRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WipImportService
{
  protected $wipRepository;
  protected string $WIP_QUANTITY_EXCEL_PATH = '\\\\192.168.1.13\\ftproot\\daily_backend_wip.csv';
  protected string $WIP_OUTS_EXCEL_PATH = '\\\\192.168.1.13\\ftproot\\daily_telford_outs.csv';

  public function __construct(WipRepository $wipRepository)
  {
    $this->wipRepository = $wipRepository;
  }

  public function autoImportWIP($importedBy = null): array
  {
    date_default_timezone_set('Asia/Manila');

    if (!file_exists($this->WIP_QUANTITY_EXCEL_PATH)) {
      return ['status' => 'error', 'message' => 'CSV file not found or inaccessible.'];
    }

    $lock = Cache::lock('auto_import_wip_lock', 600);

    if (!$lock->get()) {
      throw new Exception('The WIP import is currently running. To prevent duplicate processing, you can try again after a few minutes (up to 10 minutes).');
    }

    try {
      $existingRecords = $this->prepareExistingRecords();
      [$countCustomer, $countF3, $ignored] = [0, 0, 0];

      if (($handle = fopen($this->WIP_QUANTITY_EXCEL_PATH, 'r')) === false) {
        throw new Exception('Failed to open the CSV file.');
      }

      DB::beginTransaction();

      fgetcsv($handle, 0, ',');

      while (($row = fgetcsv($handle, 0, ',')) !== false) {
        // Log::info('Row: ' . print_r($row, true));

        // if (count($row) < 44) {
        //   Log::warning('Skipping malformed CSV row', ['row' => $row]);
        //   continue;
        // }

        $row = $this->sanitizeRow($row, $importedBy);


        $focusGroup = $row['Focus_Group'];
        // Log::info('------------------Focus Group: ' . $row['Focus_Group']);


        $lotId = $row['Lot_Id'];
        $dateLoaded = $row['Date_Loaded'];
        $dateOnly = substr($dateLoaded, 0, 10);
        $key = $focusGroup === 'F3'
          ? "{$lotId}-{$dateOnly}-F3"
          : "{$lotId}-{$dateLoaded}";

        if (isset($existingRecords[$key])) {
          continue;
        }

        if ($focusGroup === 'F3') {
          $this->wipRepository->insertF3($row);
          $countF3++;
        } else {
          $this->wipRepository->insertCustomer($row);
          $countCustomer++;
        }

        $existingRecords[$key] = true;
      }

      fclose($handle);

      DB::commit();

      return [
        'status' => 'success',
        'message' => 'Import completed successfully.',
        'f1f2' => $countCustomer,
        'f3' => $countF3
      ];
    } catch (Exception $e) {
      DB::rollBack();
      Log::error('WIP import failed', ['error' => $e->getMessage()]);
      return ['status' => 'error', 'message' => $e->getMessage()];
    } finally {
      $lock->release();
    }
  }

  private function prepareExistingRecords(): array
  {
    $records = $this->wipRepository->getExistingRecords();
    $map = [];

    foreach ($records as $r) {
      $map[$r->Focus_Group === 'F3'
        ? "{$r->Lot_Id}-{$r->dateonly}-F3"
        : "{$r->Lot_Id}-{$r->Date_Loaded}"] = true;
    }

    return $map;
  }

  private function sanitizeRow(array $row, $importedBy): array
  {
    $row = array_map(fn($v) => trim($v) === '' ? null : trim($v), $row);

    // TODO: what to do with missing values???
    // TOOD: OK response from server error ?????? 

    $now = Carbon::now();
    $row[10] = $row[10] ? Carbon::parse($row[10]) : $now; // Date_Loaded
    $row[11] = $row[11] ? Carbon::parse($row[11]) : null; // Start_Time
    $row[21] = $row[21] ? Carbon::parse($row[21]) : null; // Stage_Start_Time
    $row[22] = $row[22] ? Carbon::parse($row[22]) : null; // CCD
    $row[18] = $row[18] ? Carbon::parse($row[18]) : null; // Reqd_Time
    $row[19] = $row[19] ? Carbon::parse($row[19]) : null; // Lot_Entry_Time


    return array_combine([
      'Plant',
      'Part_Name',
      'Lead_Count',
      'Package_Name',
      'Lot_Id',
      'Station',
      'Qty',
      'Lot_Type',
      'Prod_Area',
      'Lot_Status',
      'Date_Loaded',
      'Start_Time',
      'Part_Type',
      'Part_Class',
      'Date_Code',
      'Focus_Group',
      'Process_Group',
      'Bulk',
      'Reqd_Time',
      'Lot_Entry_Time',
      'Stage',
      'Stage_Start_Time',
      'CCD',
      'Stage_Run_Days',
      'Lot_Entry_Time_Days',
      'Tray',
      'Backend_Leadtime',
      'OSL_Days',
      'BE_Group',
      'Strategy_Code',
      'CR3',
      'BE_Starttime',
      'BE_OSL_Days',
      'Body_Size',
      'Auto_Part',
      'Ramp_Time',
      'End_Customer',
      'Bake',
      'Bake_Count',
      'Test_Lot_Id',
      'Stock_Position',
      'Assy_Site',
      'Bake_Time_Temp',
      'imported_by'
    ], array_merge($row, [$importedBy]));
  }
}
