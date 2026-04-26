<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\RepairController;

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
});

require __DIR__.'/auth.php';
