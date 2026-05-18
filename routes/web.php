<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\User\NotificationController;
use App\Http\Controllers\Auth\GoogleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public & Auth Core
Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Notifications
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read_all');
});

// Modular Routes
require __DIR__.'/modules/inventory.php';
require __DIR__.'/modules/shop.php';
require __DIR__.'/modules/customer.php';
require __DIR__.'/modules/preorders.php';
require __DIR__.'/modules/repairs.php';
require __DIR__.'/modules/sales.php';

require __DIR__.'/auth.php';

// RCPAY Webhook Callback Route (Public)
Route::post('/api/rcpay-webhook', [\App\Http\Controllers\Payment\RcpayWebhookController::class, 'handle'])->middleware('throttle:60,1')->name('webhook.rcpay');

