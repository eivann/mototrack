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
    Route::get('/inventory', [InventoryController::class, 'index']);
    Route::post('/inventory', [InventoryController::class, 'store']);
    Route::get('/sales/create', [SaleController::class, 'recordSaleForm']);
Route::post('/sales/store', [SaleController::class, 'storeSale']);
Route::get('/sales/history', [SaleController::class, 'history']);
Route::get('/sales', [SaleController::class, 'recordSaleForm'])->name('sales.index');
 Route::resource('inventory', InventoryController::class);
 Route::delete('/sales/{id}', [SaleController::class, 'destroy']);
Route::get('/sales/report/receipt', [SaleController::class, 'reportReceipt']);
Route::get('/sales/{id}/receipt', [SaleController::class, 'receipt']);
Route::get('/repairs', [RepairController::class, 'index']);
Route::post('/repairs', [RepairController::class, 'store']);
Route::put('/repairs/{id}', [RepairController::class, 'update']);
Route::delete('/repairs/{id}', [RepairController::class, 'destroy']);
Route::put('/repairs/{id}/pay', [RepairController::class, 'markPaid']);
Route::get('/repairs/{id}/receipt', [RepairController::class, 'receipt']);


});

Route::middleware(['auth', 'owner'])->group(function () {
    Route::get('/inventory', [InventoryController::class, 'index']);
    Route::post('/inventory', [InventoryController::class, 'store']);
    Route::get('/preorders', [PreOrderController::class, 'index']);
});

Route::get('/customer-dashboard', function () {
    return view('customer_dashboard');
})->middleware(['auth']);



Route::get('/compatibility', [App\Http\Controllers\CustomerController::class, 'compatibilityForm'])->middleware('auth');
Route::post('/compatibility', [App\Http\Controllers\CustomerController::class, 'checkCompatibility'])->middleware('auth');
Route::post('/cart/add', [App\Http\Controllers\CustomerController::class, 'addToCart'])->middleware('auth');
Route::get('/cart', [App\Http\Controllers\CustomerController::class, 'viewCart'])->middleware('auth');
Route::get('/checkout', [App\Http\Controllers\CustomerController::class, 'checkout'])->middleware('auth');
Route::post('/checkout', [App\Http\Controllers\CustomerController::class, 'processCheckout'])->middleware('auth');
Route::get('/preorder/{id}', [CustomerController::class, 'preOrderForm'])->middleware('auth');
Route::post('/preorder', [CustomerController::class, 'storePreOrder'])->middleware('auth');
Route::get('/preorder-parts', [CustomerController::class, 'preorderParts'])->middleware('auth');
Route::get('/preorder-custom', [CustomerController::class, 'customPreorderForm'])->middleware('auth');
Route::post('/preorder-custom', [CustomerController::class, 'storeCustomPreorder'])->middleware('auth');
Route::get('/preorders', [PreOrderController::class, 'index']);
Route::post('/preorders/{id}/complete', [PreOrderController::class, 'complete']);
Route::post('/preorders/{id}/cancel', [PreOrderController::class, 'cancel']);

Route::get('/browse', [CustomerController::class, 'browse']);
Route::delete('/preorders/{id}', [PreOrderController::class, 'destroy']);
require __DIR__.'/auth.php';
