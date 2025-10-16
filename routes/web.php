<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PartNameController;
use Inertia\Inertia;
use App\Http\Controllers\General\AdminController;
use App\Http\Controllers\General\ProfileController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Controllers\DashboardController;

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

    Route::get("/", [DashboardController::class, 'index'])->name('dashboard');
    Route::get("/wip-dashboard", [DashboardController::class, 'wipDashboardIndex'])->name('wip.dashboard');
    Route::get("/pickup-dashboard", [DashboardController::class, 'pickupDashboardIndex'])->name('pickup.dashboard');
    Route::get("/residual-dashboard", [DashboardController::class, 'residualDashboardIndex'])->name('residual.dashboard');
    Route::get("/wip-table", [App\Http\Controllers\WipController::class, 'wipTable'])->name('wipTable');
    Route::get("/profile", [ProfileController::class, 'index'])->name('profile.index');
    Route::post("/change-password", [ProfileController::class, 'changePassword'])->name('changePassword');
});


Route::fallback(function () {
    return Inertia::render('404');
})->name('404');

// Part Name Routes
Route::prefix('partname')->name('partname.')->group(function () {
    Route::get('/', [PartNameController::class, 'index'])->name('index');
    Route::get('/create', [PartNameController::class, 'upsert'])->name('create');
    Route::get('/{id}/edit', [PartNameController::class, 'upsert'])->name('edit');
});
