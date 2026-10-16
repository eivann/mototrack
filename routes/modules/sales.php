<?php

use App\Http\Controllers\Sales\SaleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'owner'])->group(function () {
    Route::get('/sales', [SaleController::class, 'recordSaleForm'])->name('sales.index');
    Route::post('/sales/store', [SaleController::class, 'storeSale'])->name('sales.store');
    Route::get('/sales/history', [SaleController::class, 'history'])->name('sales.history');
    // NOTE: there is intentionally NO DELETE /sales/{id} route. Sale rows are the
    // financial, cancellation and inventory-traceability record and are never
    // deleted; cancel/expire/refund workflows change status instead.

    Route::get('/sales/{id}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');
    Route::get('/sales/{transaction}/download', [SaleController::class, 'downloadReceipt'])->name('sales.download');
    Route::post('/sales/{transaction}/complete', [SaleController::class, 'complete'])->name('sales.complete');
    
    // Refunds
    Route::post('/refunds/{id}/approve', [App\Http\Controllers\Admin\RefundController::class, 'approve'])->name('admin.refund.approve');
    Route::post('/refunds/{id}/reject', [App\Http\Controllers\Admin\RefundController::class, 'reject'])->name('admin.refund.reject');
});
