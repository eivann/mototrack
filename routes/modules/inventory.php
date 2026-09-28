<?php

use App\Http\Controllers\Inventory\PublicBrowseController;
use App\Http\Controllers\Inventory\AdminInventoryController;
use App\Http\Controllers\Inventory\InventoryAdjustmentController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/browse', [PublicBrowseController::class, 'index'])->name('browse');
Route::get('/search-parts', [PublicBrowseController::class, 'searchParts'])->middleware('throttle:300,1')->name('search.parts');
Route::get('/inventory-suggestions', [PublicBrowseController::class, 'suggestions'])->middleware('throttle:300,1')->name('inventory.suggestions');
Route::get('/compatibility', [PublicBrowseController::class, 'compatibilityForm'])->name('compatibility.view');
Route::post('/compatibility', [PublicBrowseController::class, 'checkCompatibility'])->name('compatibility.check');

Route::get('/debug-logs-temp-2026', function () {
    $file = app_path('Http/Controllers/Inventory/PublicBrowseController.php');
    return response(file_get_contents($file))->header('Content-Type', 'text/plain');
});

// Admin Routes
Route::middleware(['auth', 'owner'])->group(function () {
    // Inventory Adjustments (Damaged & Returns)
    Route::get('/inventory/adjustments', [InventoryAdjustmentController::class, 'index'])->name('inventory.adjustments.index');
    Route::post('/inventory/adjustments/damaged', [InventoryAdjustmentController::class, 'storeDamaged'])->name('inventory.adjustments.store_damaged');
    Route::post('/inventory/adjustments/returns', [InventoryAdjustmentController::class, 'storeReturn'])->name('inventory.adjustments.store_return');

    Route::resource('inventory', AdminInventoryController::class)->parameters(['inventory' => 'item']);
    Route::get('/low-stock', [AdminInventoryController::class, 'lowStock'])->name('low-stock.index');
});
