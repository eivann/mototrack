<?php

use App\Http\Controllers\Inventory\PublicBrowseController;
use App\Http\Controllers\Inventory\AdminInventoryController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/browse', [PublicBrowseController::class, 'index'])->name('browse');
Route::get('/search-parts', [PublicBrowseController::class, 'searchParts'])->middleware('throttle:60,1')->name('search.parts');
Route::get('/compatibility', [PublicBrowseController::class, 'compatibilityForm'])->name('compatibility.view');
Route::post('/compatibility', [PublicBrowseController::class, 'checkCompatibility'])->name('compatibility.check');

// Admin Routes
Route::middleware(['auth', 'owner'])->group(function () {
    Route::resource('inventory', AdminInventoryController::class)->parameters(['inventory' => 'item']);
    Route::get('/low-stock', [AdminInventoryController::class, 'lowStock'])->name('low-stock.index');
});
