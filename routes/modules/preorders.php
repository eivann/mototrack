<?php

use App\Http\Controllers\PreOrder\CustomerPreOrderController;
use App\Http\Controllers\PreOrder\AdminPreOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // Customer Pre-orders
    Route::get('/preorder-parts', [CustomerPreOrderController::class, 'parts'])->name('preorder.parts');
    Route::get('/preorder/parts', [CustomerPreOrderController::class, 'parts']); // Keep slash alias
    Route::get('/preorder-custom', [CustomerPreOrderController::class, 'customForm'])->name('preorder.custom');
    Route::get('/preorder/custom', [CustomerPreOrderController::class, 'customForm']); // Keep slash alias
    Route::get('/preorder/{id}', [CustomerPreOrderController::class, 'form'])->name('preorder.form');
    Route::post('/preorder/store', [CustomerPreOrderController::class, 'store'])->name('preorder.store');

    // Admin Pre-order Management
    Route::middleware(['owner'])->group(function () {
        Route::get('/preorders', [AdminPreOrderController::class, 'index'])->name('preorders.index');
        Route::post('/preorders/{preorder}/pay', [AdminPreOrderController::class, 'pay'])->name('preorders.pay');
        Route::post('/preorders/{preorder}/stock', [AdminPreOrderController::class, 'stock'])->name('preorders.stock');
        Route::post('/preorders/{preorder}/complete', [AdminPreOrderController::class, 'complete'])->name('preorders.complete');
        Route::post('/preorders/{preorder}/cancel', [AdminPreOrderController::class, 'cancel'])->name('preorders.cancel');

        Route::delete('/preorders/{preorder}', [AdminPreOrderController::class, 'destroy'])->name('preorders.destroy');
    });
});
