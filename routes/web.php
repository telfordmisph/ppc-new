<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\DashboardController;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\GuestMiddleware;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


// require __DIR__.'/auth.php';

$app_name = env('APP_NAME', '');

Route::redirect('/', "/$app_name");

Route::post("/$app_name/setSession", [AuthenticationController::class, 'setSession'])->name('setSession');

Route::middleware(AuthMiddleware::class)->prefix($app_name)->group(function () {
    Route::get("/", [DashboardController::class, 'index'])->name('dashboard');

    Route::get("/logout", [AuthenticationController::class, 'logout'])->name('logout');
});

Route::fallback(function () {
    return Inertia::render('404');
});
