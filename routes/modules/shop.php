<?php

use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // Cart Management
    Route::prefix('cart')->name('cart.')->group(function () {
        Route::get('/', [CartController::class, 'index'])->name('view');
        Route::post('/add/{id}', [CartController::class, 'add'])->name('add');
        Route::post('/remove/{id}', [CartController::class, 'remove'])->name('remove');
        Route::post('/increase/{id}', [CartController::class, 'increase'])->name('increase');
        Route::post('/decrease/{id}', [CartController::class, 'decrease'])->name('decrease');
        Route::post('/update/{id}', [CartController::class, 'update'])->name('update');
    });

    // Checkout Process
    Route::prefix('checkout')->name('checkout.')->group(function () {
        Route::get('/', [CheckoutController::class, 'index'])->name('view');
        Route::post('/', [CheckoutController::class, 'process'])->name('process');
        Route::get('/receipt', [CheckoutController::class, 'receipt'])->name('receipt');
        Route::get('/receipt/{transaction}/download', [CheckoutController::class, 'downloadReceipt'])->name('receipt.download');
    });
});
