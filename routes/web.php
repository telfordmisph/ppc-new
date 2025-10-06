<?php

use App\Http\Controllers\DemoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PartNameListPageController;
use Inertia\Inertia;

$app_name = env('APP_NAME', '');

// Authentication routes
require __DIR__ . '/auth.php';

// General routes
require __DIR__ . '/general.php';

Route::get("/demo", [DemoController::class, 'index'])->name('demo');

Route::fallback(function () {
    return Inertia::render('404');
})->name('404');


Route::get('/part-name-list', [PartNameListPageController::class, 'index'])->name('part-name-list');

Route::get('/test-curl', function () {
    $ch = curl_init("http://192.168.1.26/ppcportal/classes/WMdataBento.php?f=getoveralldata");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $output = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return response()->json(['error' => $error]);
    }

    return response($output);
});
