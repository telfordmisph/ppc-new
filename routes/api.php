
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WipController;
use App\Http\Controllers\PartNameController;
use App\Http\Controllers\AutoImportController;
use App\Http\Controllers\PackageCapacityController;
use App\Http\Controllers\ImportTraceController;
use App\Http\Controllers\PackageGroupController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\F3RawPackageController;
use App\Http\Controllers\F3PackageNamesController;
use App\Http\Controllers\F3Controller;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\BodySizeController;
use App\Http\Controllers\PackageBodySizeCapacityController;
use App\Http\Controllers\PickupController;
use App\Http\Controllers\AnalogCalendarController;
use App\Http\Middleware\ApiAuthMiddleware;
use App\Http\Middleware\ApiPermissionMiddleware;

Route::middleware([ApiAuthMiddleware::class])
  ->group(function () {

    Route::prefix('wip')->name('api.wip.')->group(function () {
      Route::get('/today', [WipController::class, 'getTodayWip'])->name('today');
      Route::get('/overall', [WipController::class, 'getOverallWip'])->name('overall');
      Route::get('/distinct-packages', [WipController::class, 'getDistinctPackages'])->name('distinctPackages');
      Route::get('/overall-package', [WipController::class, 'getOverallWipByPackage'])->name('overallByPackage');
      Route::get('/filter-summary-trend', [WipController::class, 'getWIPStationTrend'])->name('filterSummaryTrend');
      Route::get('/pickup', [WipController::class, 'getOverallPickUp'])->name('pickup');
      Route::get('/pickup-summary-trend', [WipController::class, 'getPackagePickUpTrend'])->name('pickupSummaryTrend');
      Route::get('/residual', [WipController::class, 'getOverallResidual'])->name('residual');
      Route::get('/residual-summary', [WipController::class, 'getPackageResidualSummary'])->name('residualSummary');
      Route::get('/package-pickup-summary', [WipController::class, 'getPackagePickUpSummary'])->name('packagePickupSummary');
      Route::get('/wip-lot-totals', [WipController::class, 'getWIPQuantityAndLotsTotal'])->name('wipLotTotals');
      Route::get('/body-size', [WipController::class, 'getWipAndLotsByBodySize'])->name('wipAndLotsByBodySize');
      // Route::get('/wip-lot-totals-new', [WipController::class, 'getWIPQuantityAndLotsTotalNew'])->name('wipLotTotalsNew');
    });

    Route::prefix('out')->name('api.out.')->group(function () {
      Route::get('/overall', [WipController::class, 'getOverallOuts'])->name('overall');
      Route::get('/out-lot-totals', [WipController::class, 'getOutQuantityAndLotsTotal'])->name('outLotTotals');
      Route::get('/overall-package', [WipController::class, 'getOverallOutByPackage'])->name('overallByPackage');
    });

    Route::prefix('wip-out')->name('api.wip.')->group(function () {
      Route::get('/trend', [WipController::class, 'getWipOutCapacitySummaryTrend'])->name('out.trend');
    });

    Route::prefix('partname')->name('api.partname.')->group(function () {
      Route::post('/', [PartNameController::class, 'store'])->name('store');

      Route::middleware(ApiPermissionMiddleware::class . ':partname_mutate')->group(function () {
        Route::patch('/{id}', [PartNameController::class, 'update'])->name('update');
        Route::delete('/{id}', [PartNameController::class, 'destroy'])->name('delete');
      });
    });

    Route::prefix('pickup')->name('api.pickup.')->group(function () {
      Route::middleware(ApiPermissionMiddleware::class . ':pickup_mutate')->group(function () {
        Route::delete('/bulk-delete', [PickupController::class, 'massGenocide'])
          ->name('massGenocide');
      });
    });

    Route::prefix('f3-raw-package')->name('api.f3.raw.package.')->group(function () {
      Route::get('/list', [F3RawPackageController::class, 'index'])->name('index');

      Route::middleware(ApiPermissionMiddleware::class . ':f3_raw_package_mutate')->group(function () {
        Route::post('/', [F3RawPackageController::class, 'store'])->name('store');
        Route::patch('/{id}', [F3RawPackageController::class, 'update'])->name('update');
        Route::delete('/{id}', [F3RawPackageController::class, 'destroy'])->name('delete');
      });
    });

    Route::prefix('f3-wip-out')->name('api.f3.')->group(function () {
      Route::middleware(ApiPermissionMiddleware::class . ':f3_mutate')->group(function () {
        Route::patch('/', [F3Controller::class, 'bulkUpdate'])->name('bulkUpdate');
      });
    });

    Route::prefix('machines')->name('api.machines.')->group(function () {
      Route::middleware(ApiPermissionMiddleware::class . ':machine_mutate')->group(function () {
        Route::patch('/', [MachineController::class, 'bulkUpdate'])->name('bulkUpdate');
      });

      Route::middleware(ApiPermissionMiddleware::class . ':machine_mutate')->group(function () {
        Route::delete('/mass-delete', [MachineController::class, 'massGenocide'])->name('massGenocide');
      });
    });

    Route::prefix('body-sizes')->name('api.body-sizes.')->group(function () {
      Route::middleware(ApiPermissionMiddleware::class . ':body_size_mutate')->group(function () {
        Route::patch('/', [BodySizeController::class, 'bulkUpdate'])->name('bulkUpdate');
      });

      Route::middleware(ApiPermissionMiddleware::class . ':body_size_mutate')->group(function () {
        Route::delete('/mass-delete', [BodySizeController::class, 'massGenocide'])->name('massGenocide');
      });

      Route::prefix('/capacity')->name('capacity.')->group(function () {
        Route::middleware(ApiPermissionMiddleware::class . ':body_size_capacity_mutate')->group(function () {
          Route::patch('/bulkUpsert', [PackageBodySizeCapacityController::class, 'bulkUpsert'])->name('bulkUpsert');
        });
      });
    });

    Route::prefix('f3-package-names')->name('api.f3.package.names.')->group(function () {
      Route::get('/', [F3PackageNamesController::class, 'getAll'])->name('getAll');

      Route::middleware(ApiPermissionMiddleware::class . ':f3_package_mutate')->group(function () {
        Route::post('/', [F3PackageNamesController::class, 'store'])->name('store');
        Route::patch('/{id}', [F3PackageNamesController::class, 'update'])->name('update');
        Route::delete('/{id}', [F3PackageNamesController::class, 'destroy'])->name('delete');
      });
    });

    Route::prefix('package')->name('api.package.')->group(function () {
      Route::get('/packages', [PackageController::class, 'getAllPackages'])->name('all');
      Route::get('/package-groups', [PackageGroupController::class, 'index'])->name('packageGroups');

      Route::middleware(ApiPermissionMiddleware::class . ':package_group_mutate')->group(function () {
        Route::post('/', [PackageGroupController::class, 'saveGroup'])->name('store');
        Route::patch('/{id}', [PackageGroupController::class, 'saveGroup'])->name('update');
        Route::delete('/{id}', [PackageGroupController::class, 'destroy'])->name('delete');
      });
    });

    Route::prefix('import-trace')->name('api.import.trace.')->group(function () {
      Route::get('/imports/{type}', [ImportTraceController::class, 'getImport'])->name('getImport');
      Route::post('/imports/{type}', [ImportTraceController::class, 'upsertImport'])->name('upsertImport');
      Route::get('/imports', [ImportTraceController::class, 'getAllLatestImports'])->name('getAllLatestImports');
    });

    Route::prefix('analog-calendar')->name('api.analog.calendar.')->group(function () {
      Route::get('/analog-calendar', [AnalogCalendarController::class, 'getWorkWeek'])->name('workweek');
    });

    Route::prefix('package-capacity')->name('api.package.capacity')->group(function () {
      Route::get('/get-trend', [PackageCapacityController::class, 'getTrend'])->name('getTrend');

      Route::middleware(ApiPermissionMiddleware::class . ':capacity_upload')->group(function () {
        Route::get('/insert', [PackageCapacityController::class, 'storeCapacity'])->name('insert');
        Route::patch('/{id}/edit', [PackageCapacityController::class, 'updateCapacity'])->name('update');
      });

      // Route::get('/insert', [PackageCapacityController::class, 'storeCapacity'])->name('insert');
      // Route::patch('/{id}/edit', [PackageCapacityController::class, 'updateCapacity'])->name('update');
    });

    Route::middleware('auth:sanctum')->group(function () {});

    Route::prefix('import')->name('import.')
      ->middleware(ApiPermissionMiddleware::class . ':import_data_all')
      ->group(function () {
        Route::post('/ftpRootImportWIP', [AutoImportController::class, 'ftpRootImportWIP'])->name('ftpRootImportWIP');
        Route::post('/ftpRootImportOUTS', [AutoImportController::class, 'ftpRootImportOUTS'])->name('ftpRootImportOUTS');
        Route::post('/manualImportWIP', [AutoImportController::class, 'manualImportWIP'])->name('manualImportWIP');
        Route::post('/manualImportOUTS', [AutoImportController::class, 'manualImportOUTS'])->name('manualImportOUTS');
        Route::post('/importF3', [AutoImportController::class, 'importF3'])->name('importF3');
        Route::post('/importPickUp', [AutoImportController::class, 'importPickUp'])->name('importPickUp');
        Route::post('/importF3PickUp', [AutoImportController::class, 'importF3PickUp'])->name('importF3PickUp');
        Route::post('/capacity', [AutoImportController::class, 'importCapacity'])->name('capacity');
      });

    Route::prefix('download')->name('api.download.')->group(function () {
      Route::get('/factoryWipOutTrendRaw', [WipController::class, 'getWipOutTrendRawData'])->name('factoryWipOutTrendRaw');
      Route::get('/factoryPickUpTrendRaw', [WipController::class, 'getPickUpTrendRawData'])->name('factoryPickUpTrendRaw');
      Route::get('/downloadCapacityTemplate', [WipController::class, 'downloadCapacityTemplate'])->name('downloadCapacityTemplate');
      Route::get('/downloadPickUpTemplate', [WipController::class, 'downloadPickUpTemplate'])->name('downloadPickUpTemplate');
      Route::get('/downloadF3PickUpTemplate', [WipController::class, 'downloadF3PickUpTemplate'])->name('downloadF3PickUpTemplate');
    });
  });


?>