<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PartNameController;
use Inertia\Inertia;
use App\Http\Controllers\General\AdminController;
use App\Http\Controllers\General\ProfileController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AutoImportController;
use App\Http\Controllers\PackageGroupController;
use App\Http\Controllers\F3RawPackageController;
use App\Http\Controllers\PackageCapacityController;

$app_name = env('APP_NAME', '');
Route::redirect('/', "/$app_name");

// Authentication routes
require __DIR__ . '/auth.php';

Route::prefix($app_name)->middleware(AuthMiddleware::class)->group(function () {
    Route::middleware(AdminMiddleware::class)->group(function () {
        Route::get("/admin", [AdminController::class, 'index'])->name('admin');
        Route::get("/new-admin", [AdminController::class, 'index_addAdmin'])->name('index_addAdmin');
        Route::post("/add-admin", [AdminController::class, 'addAdmin'])->name('addAdmin');
        Route::post("/remove-admin", [AdminController::class, 'removeAdmin'])->name('removeAdmin');
        Route::patch("/change-admin-role", [AdminController::class, 'changeAdminRole'])->name('changeAdminRole');
    });

    Route::prefix('import')->name('import.')->group(function () {
        Route::get("/f1f2", [AutoImportController::class, 'renderF1F2ImportPage'])->name('index');
        Route::get("/f3", [AutoImportController::class, 'renderF3ImportPage'])->name('f3.index');
    });

    Route::get("/", [DashboardController::class, 'index'])->name('dashboard');
    Route::get("/wip-trend", [DashboardController::class, 'wipDashboardIndex'])->name('wip.trend');
    Route::get("/pickup-dashboard", [DashboardController::class, 'pickupDashboardIndex'])->name('pickup.dashboard');
    Route::get("/residual-dashboard", [DashboardController::class, 'residualDashboardIndex'])->name('residual.dashboard');
    Route::get("/wip-station", [App\Http\Controllers\WipController::class, 'wipStation'])->name('wipTable');
    Route::get("/profile", [ProfileController::class, 'index'])->name('profile.index');
    Route::post("/change-password", [ProfileController::class, 'changePassword'])->name('changePassword');

    // Part Name Routes
    Route::prefix('partname')->name('partname.')->group(function () {
        Route::get('/', [PartNameController::class, 'index'])->name('index');
        Route::get('/create', [PartNameController::class, 'upsert'])->name('create');
        Route::get('/{id}/edit', [PartNameController::class, 'upsert'])->name('edit');
    });

    Route::prefix('package')->name('package.group.')->group(function () {
        Route::get('/', [PackageGroupController::class, 'index'])->name('index');
        Route::get('/{id}/edit', [PackageGroupController::class, 'upsert'])->name('edit');
    });

    Route::prefix('f3-raw-package')->name('f3.raw.package.')->group(function () {
        Route::get('/', [F3RawPackageController::class, 'index'])->name('index');
        Route::get('/create', [F3RawPackageController::class, 'upsert'])->name('create');
        Route::get('/{id}/edit', [F3RawPackageController::class, 'upsert'])->name('edit');
    });

    Route::prefix('package-capacity')->name('package.capacity.')->group(function () {
        Route::get('/', [PackageCapacityController::class, 'getSummaryLatestAndPrevious'])->name('index');
        Route::get('/create', [PackageCapacityController::class, 'storeCapacity'])->name('create');
        Route::get('/{id}/edit', [PackageCapacityController::class, 'updateCapacity'])->name('edit');
    });
});


Route::fallback(function () {
    return Inertia::render('404');
})->name('404');
