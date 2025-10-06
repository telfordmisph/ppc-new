
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataController;
use App\Http\Controllers\WipController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
  return $request->user();
});

Route::get('/total-quantity', [DataController::class, 'getTotalQuantity']);
Route::get('/wip-data', [DataController::class, 'getWIP']);
Route::get('/today-wip', [WipController::class, 'getTodayWip']);
Route::get('/overall-wip', [WipController::class, 'getOverallWip']);
Route::get('/overall-pickup', [WipController::class, 'getOverallPickUp']);
Route::get('/package-pickup-summary', [WipController::class, 'getPackagePickUpSummary']);
Route::get('/wip-quantity-and-lot-totals', [WipController::class, 'getWIPQuantityAndLotsTotal']);
Route::get('/wip-quantity-and-lot-totals-pl', [WipController::class, 'getWIPQuantityAndLotsTotalPL']);
?>