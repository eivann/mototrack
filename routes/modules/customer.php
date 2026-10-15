<?php

use App\Http\Controllers\Customer\CustomerDashboardController;
use App\Http\Controllers\Customer\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/customer/dashboard', [CustomerDashboardController::class, 'index'])->name('customer.dashboard');
    
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Both cancellation entry points resolve to the same transaction_reference and
    // therefore share identical rules: a multi-item reservation is never partially
    // cancelled, whichever button the customer used.
    Route::post('/orders/{id}/cancel', [App\Http\Controllers\Customer\CustomerOrderController::class, 'cancel'])->name('customer.order.cancel');
    Route::post('/reservations/{reference}/cancel', [App\Http\Controllers\Customer\CustomerOrderController::class, 'cancelByReference'])->name('customer.reservation.cancel');
    Route::post('/orders/{id}/refund', [App\Http\Controllers\Customer\CustomerOrderController::class, 'requestRefund'])->name('customer.order.refund');

// Uses a distinct /customer/preorders prefix so it cannot shadow the
    // owner-only /preorders/{preorder}/cancel route registered in preorders.php.
    Route::post('/customer/preorders/{id}/cancel', [App\Http\Controllers\PreOrder\CustomerPreOrderController::class, 'cancel'])->name('customer.preorder.cancel');
});
