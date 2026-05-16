<?php

use App\Http\Controllers\PreOrder\CustomerPreOrderController;
use App\Http\Controllers\PreOrder\AdminPreOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // Customer Pre-orders
    Route::get('/preorder/parts', [CustomerPreOrderController::class, 'parts'])->name('preorder.parts');
    Route::get('/preorder/custom', [CustomerPreOrderController::class, 'customForm'])->name('preorder.custom');
    Route::get('/preorder/{id}', [CustomerPreOrderController::class, 'form'])->name('preorder.form');
    Route::post('/preorder/store', [CustomerPreOrderController::class, 'store'])->name('preorder.store');
    Route::get('/preorder/verify/{id}', [CustomerPreOrderController::class, 'verify'])->name('preorder.verify');

    // Admin Pre-order Management
    Route::middleware(['owner'])->group(function () {
        Route::get('/preorders', [AdminPreOrderController::class, 'index'])->name('preorders.index');
        Route::post('/preorders/{preorder}/complete', [AdminPreOrderController::class, 'complete'])->name('preorders.complete');
        Route::post('/preorders/{preorder}/cancel', [AdminPreOrderController::class, 'cancel'])->name('preorders.cancel');
        Route::post('/preorders/{preorder}/pay', [AdminPreOrderController::class, 'markPaid'])->name('preorders.pay');
        Route::delete('/preorders/{preorder}', [AdminPreOrderController::class, 'destroy'])->name('preorders.destroy');
    });
});
