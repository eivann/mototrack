<?php

use App\Http\Controllers\Payment\OwnerPaymentController;
use App\Http\Controllers\Payment\PaymentController;
use Illuminate\Support\Facades\Route;

// RD2-R2: manual GCash QR payments. Every endpoint refuses (404) while PAYMENTS_GCASH_QR_ENABLED is off.
Route::middleware('auth')->group(function () {
    Route::get('/payments/gcash-qr', [PaymentController::class, 'qr'])->name('payments.qr');
    Route::get('/payments/proof/{claim}', [PaymentController::class, 'proof'])->whereNumber('claim')->name('payments.proof');
    Route::get('/payments/{type}/{key}', [PaymentController::class, 'show'])->whereIn('type', ['reservation', 'preorder'])->name('payments.show');
    Route::post('/payments/{type}/{key}', [PaymentController::class, 'submit'])->whereIn('type', ['reservation', 'preorder'])
        ->middleware('throttle:8,1')->name('payments.submit');

    Route::middleware('owner')->prefix('owner/payments')->name('owner.payments.')->group(function () {
        Route::get('/', [OwnerPaymentController::class, 'index'])->name('index');
        Route::post('/{claim}/approve', [OwnerPaymentController::class, 'approve'])->whereNumber('claim')->name('approve');
        Route::post('/{claim}/reject', [OwnerPaymentController::class, 'reject'])->whereNumber('claim')->name('reject');
    });
});
