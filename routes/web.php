<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\RepairController;
use App\Http\Controllers\PreOrderController;
use App\Http\Controllers\CustomerController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Customer Dashboard
    Route::get('/customer-dashboard', function () {
        $repairs = \App\Models\Repair::where('customer_name', auth()->user()->name)->latest()->get();
        return view('customer_dashboard', compact('repairs'));
    })->name('customer.dashboard');

    // Checkout & Cart
    Route::post('/checkout', [CustomerController::class, 'processCheckout']);
    Route::post('/cart/remove/{id}', [CustomerController::class, 'removeFromCart']);
    Route::get('/compatibility', [CustomerController::class, 'compatibilityForm']);
    Route::post('/compatibility', [CustomerController::class, 'checkCompatibility']);
    Route::post('/cart/add/{id}', [CustomerController::class, 'addToCart']);
    Route::get('/cart', [CustomerController::class, 'viewCart']);
    Route::get('/checkout', [CustomerController::class, 'checkout']);
    Route::post('/cart/increase/{id}', [CustomerController::class, 'increase']);
    Route::post('/cart/decrease/{id}', [CustomerController::class, 'decrease']);
    Route::post('/cart/update/{id}', [CustomerController::class, 'updateCart']);
    Route::get('/receiptcart', [CustomerController::class, 'receipt']);
    Route::get('/browse', [CustomerController::class, 'browse']);
    Route::get('/search-parts', [CustomerController::class, 'searchParts']);

    // Pre-orders
    Route::get('/preorder/{id}', [CustomerController::class, 'preOrderForm']);
    Route::post('/preorder', [CustomerController::class, 'storePreOrder']);
    Route::get('/preorder-parts', [CustomerController::class, 'preorderParts']);
    Route::get('/preorder-custom', [CustomerController::class, 'customPreorderForm']);
    Route::post('/preorder-custom', [CustomerController::class, 'storeCustomPreorder']);
});

Route::middleware(['auth', 'owner'])->group(function () {
    // Inventory
    Route::resource('inventory', InventoryController::class);
    Route::get('/low-stock', [InventoryController::class, 'lowStock'])->name('low-stock.index');

    // Sales POS
    Route::get('/sales/create', [SaleController::class, 'recordSaleForm']);
    Route::post('/sales/store', [SaleController::class, 'storeSale']);
    Route::get('/sales/history', [SaleController::class, 'history']);
    Route::get('/sales', [SaleController::class, 'recordSaleForm'])->name('sales.index');
    Route::delete('/sales/{id}', [SaleController::class, 'destroy']);
    Route::get('/sales/report/receipt', [SaleController::class, 'reportReceipt']);
    Route::get('/sales/{id}/receipt', [SaleController::class, 'receipt']);

    // Repairs
    Route::get('/repairs', [RepairController::class, 'index']);
    Route::post('/repairs', [RepairController::class, 'store']);
    Route::put('/repairs/{id}', [RepairController::class, 'update']);
    Route::delete('/repairs/{id}', [RepairController::class, 'destroy']);
    Route::put('/repairs/{id}/pay', [RepairController::class, 'markPaid']);
    Route::get('/repairs/{id}/receipt', [RepairController::class, 'receipt']);

    // Pre-orders Management
    Route::get('/preorders', [PreOrderController::class, 'index']);
    Route::post('/preorders/{id}/complete', [PreOrderController::class, 'complete']);
    Route::post('/preorders/{id}/cancel', [PreOrderController::class, 'cancel']);
    Route::delete('/preorders/{id}', [PreOrderController::class, 'destroy']);
});

require __DIR__.'/auth.php';
