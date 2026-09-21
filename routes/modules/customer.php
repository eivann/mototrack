<?php

use App\Http\Controllers\Customer\CustomerDashboardController;
use App\Http\Controllers\Customer\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/customer/dashboard', [CustomerDashboardController::class, 'index'])->name('customer.dashboard');
    
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/orders/{id}/cancel', [App\Http\Controllers\Customer\CustomerOrderController::class, 'cancel'])->name('customer.order.cancel');
    Route::post('/orders/{id}/refund', [App\Http\Controllers\Customer\CustomerOrderController::class, 'requestRefund'])->name('customer.order.refund');
});
