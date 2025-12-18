
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WipController;
use App\Http\Controllers\PartNameController;
use App\Http\Controllers\AutoImportController;
use App\Http\Controllers\PackageCapacityController;
use App\Http\Controllers\ImportTraceController;
use App\Http\Controllers\PackageGroupController;
use App\Http\Controllers\F3RawPackageController;
use App\Http\Controllers\F3PackageNamesController;
use App\Http\Controllers\F3Controller;
use App\Http\Controllers\AnalogCalendarController;
use App\Http\Middleware\ApiAuthMiddleware;


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
      // Route::get('/wip-lot-totals-new', [WipController::class, 'getWIPQuantityAndLotsTotalNew'])->name('wipLotTotalsNew');
    });

    Route::prefix('wip-out')->name('api.wip.')->group(function () {
      Route::get('/trend', [WipController::class, 'getWipOutCapacitySummaryTrend'])->name('out.trend');
    });

    Route::prefix('partname')->name('api.partname.')->group(function () {
      Route::post('/', [PartNameController::class, 'store'])->name('store');
      Route::patch('/{id}', [PartNameController::class, 'update'])->name('update');
      Route::delete('/{id}', [PartNameController::class, 'destroy'])->name('delete');
    });

    Route::prefix('f3-raw-package')->name('api.f3.raw.package.')->group(function () {
      Route::get('/list', [F3RawPackageController::class, 'index'])->name('index');
      Route::post('/', [F3RawPackageController::class, 'store'])->name('store');
      Route::patch('/{id}', [F3RawPackageController::class, 'update'])->name('update');
      Route::delete('/{id}', [F3RawPackageController::class, 'destroy'])->name('delete');
    });

    Route::prefix('f3-wip-out')->name('api.f3.')->group(function () {
      Route::patch('/', [F3Controller::class, 'bulkUpdate'])->name('bulkUpdate');
    });

    Route::prefix('f3-package-names')->name('api.f3.package.names.')->group(function () {
      Route::get('/', [F3PackageNamesController::class, 'getAll'])->name('getAll');
      Route::post('/', [F3PackageNamesController::class, 'store'])->name('store');
      Route::patch('/{id}', [F3PackageNamesController::class, 'update'])->name('update');
      Route::delete('/{id}', [F3PackageNamesController::class, 'destroy'])->name('delete');
    });

    Route::prefix('package')->name('api.package.')->group(function () {
      Route::get('/package-groups', [PackageGroupController::class, 'index'])->name('packageGroups');
      Route::post('/', [PackageGroupController::class, 'saveGroup'])->name('store');
      Route::patch('/{id}', [PackageGroupController::class, 'saveGroup'])->name('update');
      Route::delete('/{id}', [PackageGroupController::class, 'destroy'])->name('delete');
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
      Route::get('/insert', [PackageCapacityController::class, 'storeCapacity'])->name('insert');
      Route::get('/get-trend', [PackageCapacityController::class, 'getTrend'])->name('getTrend');
      Route::patch('/{id}/edit', [PackageCapacityController::class, 'updateCapacity'])->name('update');
    });

    Route::middleware('auth:sanctum')->group(function () {});

    Route::prefix('import')->name('import.')->group(function () {
      Route::post('/autoImportWIP', [AutoImportController::class, 'autoImportWIP'])->name('autoImportWIP');
      Route::post('/importF3WIP', [AutoImportController::class, 'importF3WIP'])->name('importF3WIP');
      Route::post('/importF3', [AutoImportController::class, 'importF3'])->name('importF3');
      Route::post('/importF3OUTS', [AutoImportController::class, 'importF3OUTS'])->name('importF3OUTS');
      Route::post('/autoImportWIPOUTS', [AutoImportController::class, 'autoImportWIPOUTS'])->name('autoImportWIPOUTS');
      Route::post('/capacity', [AutoImportController::class, 'importCapacity'])->name('capacity');
    });

    Route::prefix('download')->name('api.download.')->group(function () {
      Route::get('/factoryWipOutTrendRaw', [WipController::class, 'getWipOutTrendRawData'])->name('factoryWipOutTrendRaw');
      Route::get('/factoryPickUpTrendRaw', [WipController::class, 'getPickUpTrendRawData'])->name('factoryPickUpTrendRaw');
    });
  });


?>