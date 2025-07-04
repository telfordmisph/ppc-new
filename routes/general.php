<?php

use App\Http\Controllers\General\AdminController;
use App\Http\Middleware\AuthMiddleware;
use Illuminate\Support\Facades\Route;

$app_name = env('APP_NAME', '');

Route::prefix($app_name)->middleware(AuthMiddleware::class)->group(function () {




  Route::get("/admin", [AdminController::class, 'index'])->name('admin');
  Route::post("/add-admin", [AdminController::class, 'addAdmin'])->name('addAdmin');
  Route::post("/remove-admin", [AdminController::class, 'removeAdmin'])->name('removeAdmin');
  Route::patch("/change-admin-role", [AdminController::class, 'changeAdminRole'])->name('changeAdminRole');
});
