<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WipImportService;

class AutoImportWipCommand extends Command
{
  protected $signature = 'auto:import-wip {--user_id=}';
  protected $description = 'Automatically import WIP data from daily_backend_wip.csv';
  protected $importService;

  public function __construct(WipImportService $importService)
  {
    parent::__construct();
    $this->importService = $importService;
  }

  public function handle()
  {
    $userId = $this->option('user_id');
    $result = $this->importService->ftpRootImportF1F2WIP($userId);

    $this->info($result['message']);
    return $result['status'] === 'success' ? 0 : 1;
  }
}
