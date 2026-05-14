<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\RepairController;
use App\Http\Controllers\PreOrderController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\Auth\GoogleController;

// Google OAuth
Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/browse', [CustomerController::class, 'browse'])->name('browse');
Route::get('/search-parts', [CustomerController::class, 'searchParts'])->middleware('throttle:60,1')->name('search.parts');
Route::get('/compatibility', [CustomerController::class, 'compatibilityForm'])->name('compatibility.view');
Route::post('/compatibility', [CustomerController::class, 'checkCompatibility'])->name('compatibility.check');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Customer Dashboard
    Route::get('/customer-dashboard', [CustomerController::class, 'dashboard'])->name('customer.dashboard');

    // Checkout & Cart
    Route::get('/cart', [CustomerController::class, 'viewCart'])->name('cart.view');
    Route::post('/cart/add/{id}', [CustomerController::class, 'addToCart'])->name('cart.add');
    Route::post('/cart/remove/{id}', [CustomerController::class, 'removeFromCart'])->name('cart.remove');
    Route::post('/cart/increase/{id}', [CustomerController::class, 'increase'])->name('cart.increase');
    Route::post('/cart/decrease/{id}', [CustomerController::class, 'decrease'])->name('cart.decrease');
    Route::post('/cart/update/{id}', [CustomerController::class, 'updateCart'])->name('cart.update');

    Route::get('/checkout', [CustomerController::class, 'checkout'])->name('checkout.view');
    Route::post('/checkout', [CustomerController::class, 'processCheckout'])->name('checkout.process');
    Route::get('/receiptcart', [CustomerController::class, 'receipt'])->name('checkout.receipt');

    // Pre-orders
    Route::get('/preorder-parts', [CustomerController::class, 'preorderParts'])->name('preorder.parts');
    Route::get('/preorder-custom', [CustomerController::class, 'customPreorderForm'])->name('preorder.custom');
    Route::get('/preorder/{id}', [CustomerController::class, 'preOrderForm'])->name('preorder.form');
    Route::post('/preorder/store', [CustomerController::class, 'storePreOrder'])->name('preorder.store');
    Route::get('/preorder/verify/{id}', [CustomerController::class, 'verifyPreorder'])->name('preorder.verify');

    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read_all');
});

Route::middleware(['auth', 'owner'])->group(function () {
    // Inventory
    Route::resource('inventory', InventoryController::class)->parameters(['inventory' => 'item']);
    Route::get('/low-stock', [InventoryController::class, 'lowStock'])->name('low-stock.index');

    // Sales POS
    Route::get('/sales', [SaleController::class, 'recordSaleForm'])->name('sales.index');
    Route::post('/sales/store', [SaleController::class, 'storeSale'])->name('sales.store');
    Route::get('/sales/history', [SaleController::class, 'history'])->name('sales.history');
    Route::delete('/sales/{id}', [SaleController::class, 'destroy'])->name('sales.destroy');
    Route::get('/sales/report/receipt', [SaleController::class, 'reportReceipt'])->name('sales.report_receipt');
    Route::get('/sales/{id}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');

    // Repairs
    Route::get('/repairs', [RepairController::class, 'index'])->name('repairs.index');
    Route::post('/repairs', [RepairController::class, 'store'])->name('repairs.store');
    Route::put('/repairs/{repair}', [RepairController::class, 'update'])->name('repairs.update');
    Route::delete('/repairs/{repair}', [RepairController::class, 'destroy'])->name('repairs.destroy');
    Route::put('/repairs/{repair}/pay', [RepairController::class, 'markPaid'])->name('repairs.pay');
    Route::get('/repairs/{repair}/receipt', [RepairController::class, 'receipt'])->name('repairs.receipt');

    // Pre-orders Management
    Route::get('/preorders', [PreOrderController::class, 'index'])->name('preorders.index');
    Route::post('/preorders/{preorder}/complete', [PreOrderController::class, 'complete'])->name('preorders.complete');
    Route::post('/preorders/{preorder}/cancel', [PreOrderController::class, 'cancel'])->name('preorders.cancel');
    Route::post('/preorders/{preorder}/pay', [PreOrderController::class, 'markPaid'])->name('preorders.pay');
    Route::delete('/preorders/{preorder}', [PreOrderController::class, 'destroy'])->name('preorders.destroy');
});

require __DIR__.'/auth.php';
