<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Controllers\DashboardController;

$app_name = env('APP_NAME', '');

Route::redirect('/', "/$app_name");

Route::prefix($app_name)->middleware(AuthMiddleware::class)->group(function () {
    Route::get("/", [DashboardController::class, 'index'])->name('dashboard');
});

Route::fallback(function () {
    return Inertia::render('404');
})->name('404');

// Authentication routes
require __DIR__ . '/auth.php';
