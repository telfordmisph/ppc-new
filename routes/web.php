<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\DashboardController;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\GuestMiddleware;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

$app_name = env('APP_NAME', '');

Route::redirect('/', "/$app_name");

// Route::post("/$app_name/setSession", [AuthenticationController::class, 'setSession'])->name('setSession');

// Route::middleware(AuthMiddleware::class)->prefix($app_name)->group(function () {
//     Route::get("/", [DashboardController::class, 'index'])->name('dashboard');

//     Route::get("/logout", [AuthenticationController::class, 'logout'])->name('logout');
// });


// Route::get("/$app_name/unauthorized", function () {
//     return Inertia::render('Unauthorized');
// });

// Route::fallback(function () {
//     return Inertia::render('404');
// });

Route::prefix($app_name)->group(function () {
    Route::post("/setSession", [AuthenticationController::class, 'setSession'])->name('setSession');

    Route::middleware(AuthMiddleware::class)->group(function () {
        Route::get("/", [DashboardController::class, 'index'])->name('dashboard');

        Route::get("/logout", [AuthenticationController::class, 'logout'])->name('logout');
    });


    Route::get("/unauthorized", function () {
        return Inertia::render('Unauthorized');
    })->name(name: 'unauthorized');
});

Route::fallback(function () {
    return Inertia::render('404');
});
