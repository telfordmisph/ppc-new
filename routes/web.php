<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

$app_name = env('APP_NAME', '');

Route::redirect('/', "/$app_name");

// Authentication routes
require __DIR__ . '/auth.php';


Route::fallback(function () {
    return Inertia::render('404');
})->name('404');
