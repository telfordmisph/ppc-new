<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Controllers\DashboardController;

$app_name = env('APP_NAME', '');

// Authentication routes
require __DIR__ . '/auth.php';

// General routes
require __DIR__ . '/general.php';

Route::fallback(function () {
    return Inertia::render('404');
})->name('404');
