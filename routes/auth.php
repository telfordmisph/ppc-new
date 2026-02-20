<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\DashboardController;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\SessionMiddleware;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

$app_name = env('APP_NAME', '');

Route::prefix($app_name)->withoutMiddleware([SessionMiddleware::class, AuthMiddleware::class])->group(function () {
  Route::post("/setSession", [AuthenticationController::class, 'setSession'])->name('setSession');

  Route::get("/logout", [AuthenticationController::class, 'logout'])->name('logout');

  Route::get("/unauthorized", function () {
    return Inertia::render('Unauthorized');
  })->name(name: 'unauthorized');
});
