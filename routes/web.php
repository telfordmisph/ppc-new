<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PartNameController;
use Inertia\Inertia;
use App\Http\Controllers\General\AdminController;
use App\Http\Controllers\General\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AutoImportController;
use App\Http\Controllers\PackageGroupController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\BodySizeController;
use App\Http\Controllers\F3RawPackageController;
use App\Http\Controllers\PackageCapacityController;
use App\Http\Controllers\PackageBodySizeCapacityController;
use App\Http\Controllers\F3PackageNamesController;
use App\Http\Controllers\PickupController;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Controllers\F3Controller;

$app_name = env('APP_NAME', '');
Route::redirect('/', "/$app_name");

// Authentication routes
require __DIR__ . '/auth.php';


Route::get("/admin", [AdminController::class, 'index'])->name('admin');
Route::get("/new-admin", [AdminController::class, 'index_addAdmin'])->name('index_addAdmin');
Route::post("/add-admin", [AdminController::class, 'addAdmin'])->name('addAdmin');
Route::post("/remove-admin", [AdminController::class, 'removeAdmin'])->name('removeAdmin');
Route::patch("/change-admin-role", [AdminController::class, 'changeAdminRole'])->name('changeAdminRole');

Route::prefix('import')->name('import.')->group(function () {
    Route::middleware(AuthMiddleware::class . ':import_data_all')->group(function () {
        // Route::middleware(AuthMiddleware::class)->group(function () {
        Route::get("/f1f2", [AutoImportController::class, 'renderF1F2ImportPage'])->name('index');
    });
    Route::middleware(AuthMiddleware::class . ':import_data_all')->group(function () {
        // Route::middleware(AuthMiddleware::class)->group(function () {
        Route::get("/f3", [AutoImportController::class, 'renderF3ImportPage'])->name('f3.index');
    });
    Route::middleware(AuthMiddleware::class . ':import_data_all')->group(function () {
        Route::get("/pickup", [AutoImportController::class, 'renderPickUpImportPage'])->name('pickup.index');
    });
    Route::middleware(AuthMiddleware::class . ':import_data_all')->group(function () {
        Route::get("/f3-pickup", [AutoImportController::class, 'renderF3PickUpImportPage'])->name('f3.pickup.index');
    });
});

Route::middleware(AuthMiddleware::class)->group(function () {
    Route::get("/", [DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware(AuthMiddleware::class)->group(function () {
    Route::get("/wip-trend", [DashboardController::class, 'wipDashboardIndex'])->name('wip.trend');
});

Route::middleware(AuthMiddleware::class)->group(function () {
    Route::get("/out-trend", [DashboardController::class, 'outDashboardIndex'])->name('out.trend');
});

Route::middleware(AuthMiddleware::class)->group(function () {
    Route::get("/pickup-dashboard", [DashboardController::class, 'pickupDashboardIndex'])->name('pickup.dashboard');
});
Route::middleware(AuthMiddleware::class)->group(function () {
    Route::get("/pickup-list", [PickupController::class, 'index'])->name('pickup.index');
});
Route::middleware(AuthMiddleware::class)->group(function () {
    Route::get("/residual-dashboard", [DashboardController::class, 'residualDashboardIndex'])->name('residual.dashboard');
});
Route::middleware(AuthMiddleware::class)->group(function () {
    Route::get("/wip-station", [App\Http\Controllers\WipController::class, 'wipStation'])->name('wipTable');
});
Route::middleware(AuthMiddleware::class)->group(function () {
    Route::get("/body-size", [App\Http\Controllers\WipController::class, 'bodySize'])->name('bodySize');
});

Route::get("/profile", [ProfileController::class, 'index'])->name('profile.index');
Route::post("/change-password", [ProfileController::class, 'changePassword'])->name('changePassword');

Route::prefix('partname')->name('partname.')->group(function () {
    Route::middleware(AuthMiddleware::class)->group(function () {
        Route::get('/', [PartNameController::class, 'index'])->name('index');
    });
    Route::middleware(AuthMiddleware::class . ':partname_insert')->group(function () {
        Route::get('/create', [PartNameController::class, 'upsert'])->name('create');
    });
    Route::middleware(AuthMiddleware::class . ':partname_insert')->group(function () {
        Route::get('/create-many', [PartNameController::class, 'insertMany'])->name('createMany');
    });
    Route::middleware(AuthMiddleware::class . ':partname_edit')->group(function () {
        Route::get('/{id}/edit', [PartNameController::class, 'upsert'])->name('edit');
    });
});

Route::prefix('package')->group(function () {
    Route::middleware(AuthMiddleware::class)->group(function () {
        Route::get('/', [PackageController::class, 'index'])->name('index');
    });

    Route::middleware(AuthMiddleware::class)->name('package.group.')->group(function () {
        Route::middleware(AuthMiddleware::class)->group(function () {
            Route::get('/', [PackageGroupController::class, 'index'])->name('index');
        });
        Route::middleware(AuthMiddleware::class . ':package_group_edit')->group(function () {
            Route::get('/{id}/edit', [PackageController::class, 'upsert'])->name('edit');
        });
        Route::middleware(AuthMiddleware::class . ':package_group_insert')->group(function () {
            Route::get('/create', [PackageController::class, 'upsert'])->name('create');
        });
    });
});

Route::prefix('f3-wip-out')->name('f3.')->group(function () {
    Route::prefix('list')->name('list.')->group(function () {
        Route::middleware(AuthMiddleware::class)->group(function () {
            Route::get('/', [F3Controller::class, 'index'])->name('index');
        });
        Route::middleware(AuthMiddleware::class . ':f3_edit')->group(function () {
            Route::get('/{id}/edit', [F3Controller::class, 'upsert'])->name('edit');
        });
    });
});

Route::prefix('f3')->name('f3.')->group(function () {

    Route::prefix('package')->name('package.')->group(function () {
        Route::middleware(AuthMiddleware::class)->group(function () {
            Route::get('/', [F3PackageNamesController::class, 'index'])->name('index');
        });
        Route::middleware(AuthMiddleware::class . ':f3_package_edit')->group(function () {
            Route::get('/{id}/edit', [F3PackageNamesController::class, 'upsert'])->name('edit');
        });
        Route::middleware(AuthMiddleware::class . ':f3_package_insert')->group(function () {
            Route::get('/create', [F3PackageNamesController::class, 'upsert'])->name('create');
        });
    });

    Route::prefix('raw-package')->name('raw.package.')->group(function () {
        Route::middleware(AuthMiddleware::class)->group(function () {
            Route::get('/', [F3RawPackageController::class, 'index'])->name('index');
        });
        Route::middleware(AuthMiddleware::class . ':f3_raw_package_edit')->group(function () {
            Route::get('/{id}/edit', [F3RawPackageController::class, 'upsert'])->name('edit');
        });
        Route::middleware(AuthMiddleware::class . ':f3_raw_package_insert')->group(function () {
            Route::get('/create', [F3RawPackageController::class, 'upsert'])->name('create');
        });
        Route::middleware(AuthMiddleware::class . ':f3_raw_package_insert')->group(function () {
            Route::get('/create-many', [F3RawPackageController::class, 'insertMany'])->name('createMany');
        });
    });
});

Route::prefix('package-body-size-capacity')->name('package.body_size.capacity.')->group(function () {
    Route::middleware(AuthMiddleware::class)->group(function () {
        Route::get('/', [PackageBodySizeCapacityController::class, 'index'])->name('index');
    });
    Route::middleware(AuthMiddleware::class)->group(function () {
        Route::get('/body-sizes', [BodySizeController::class, 'index'])->name('body-sizes');
    });
    Route::middleware(AuthMiddleware::class)->group(function () {
        Route::get('/machines', [MachineController::class, 'index'])->name('machines');
    });
});

Route::prefix('package-capacity')->name('package.capacity.')->group(function () {
    Route::middleware(AuthMiddleware::class)->group(function () {
        Route::get('/', [PackageCapacityController::class, 'getSummaryLatestAndPrevious'])->name('index');
    });

    // Route::middleware(AuthMiddleware::class . ':capacity_read')->group(function () {
    //     Route::get('/create', [PackageCapacityController::class, 'storeCapacity'])->name('create');
    // });
    // Route::middleware(AuthMiddleware::class . ':capacity_read')->group(function () {
    //     Route::get('/{id}/edit', [PackageCapacityController::class, 'updateCapacity'])->name('edit');
    // });

    Route::prefix('upload')->name('upload.')->group(function () {
        Route::middleware(AuthMiddleware::class . ':capacity_upload')->group(function () {
            Route::get('/', [PackageCapacityController::class, 'upload'])->name('index');
        });
    });
});


Route::fallback(function () {
    return Inertia::render('404');
})->name('404');
