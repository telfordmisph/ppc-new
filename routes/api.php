
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataController;
use App\Http\Controllers\WipController;
use App\Http\Controllers\PartNameController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
  return $request->user();
});

Route::prefix('wip')->name('api.wip.')->group(function () {
  Route::get('/total-quantity', [DataController::class, 'getTotalQuantity'])->name('totalQuantity');
  Route::get('/today', [WipController::class, 'getTodayWip'])->name('today');
  Route::get('/overall', [WipController::class, 'getOverallWip'])->name('overall');
  Route::get('/filter-summary', [WipController::class, 'getWIPFilterSummary'])->name('filterSummary');
  Route::get('/pickup', [WipController::class, 'getOverallPickUp'])->name('pickup');
  Route::get('/residual', [WipController::class, 'getOverallResidual'])->name('residual');
  Route::get('/residual-summary', [WipController::class, 'getPackageResidualSummary'])->name('residualSummary');
  Route::get('/package-pickup-summary', [WipController::class, 'getPackagePickUpSummary'])->name('packagePickupSummary');
  Route::get('/quantity-lot-totals', [WipController::class, 'getWIPQuantityAndLotsTotal'])->name('quantityLotTotals');
  Route::get('/quantity-lot-totals-pl', [WipController::class, 'getWIPQuantityAndLotsTotalPL'])->name('quantityLotTotalsPL');
});

Route::prefix('partname')->name('api.partname.')->group(function () {
  Route::post('/', [PartNameController::class, 'store'])->name('store');
  Route::patch('/{id}', [PartNameController::class, 'update'])->name('update');
  Route::delete('/{id}', [PartNameController::class, 'destroy'])->name('delete');
});

?>