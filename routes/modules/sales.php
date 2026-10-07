<?php

use App\Http\Controllers\Sales\SaleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'owner'])->group(function () {
    Route::get('/sales', [SaleController::class, 'recordSaleForm'])->name('sales.index');
    Route::post('/sales/store', [SaleController::class, 'storeSale'])->name('sales.store');
    Route::get('/sales/history', [SaleController::class, 'history'])->name('sales.history');
    Route::delete('/sales/{id}', [SaleController::class, 'destroy'])->name('sales.destroy');
    Route::get('/sales/report/receipt', [SaleController::class, 'reportReceipt'])->name('sales.report_receipt');
    Route::get('/sales/{id}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');
    Route::get('/sales/{transaction}/download', [SaleController::class, 'downloadReceipt'])->name('sales.download');
    Route::post('/sales/{transaction}/complete', [SaleController::class, 'complete'])->name('sales.complete');
    
    // Refunds
    Route::post('/refunds/{id}/approve', [App\Http\Controllers\Admin\RefundController::class, 'approve'])->name('admin.refund.approve');
    Route::post('/refunds/{id}/reject', [App\Http\Controllers\Admin\RefundController::class, 'reject'])->name('admin.refund.reject');
});
