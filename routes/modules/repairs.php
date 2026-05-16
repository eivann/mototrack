<?php

use App\Http\Controllers\Repair\RepairController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'owner'])->group(function () {
    Route::get('/repairs', [RepairController::class, 'index'])->name('repairs.index');
    Route::post('/repairs', [RepairController::class, 'store'])->name('repairs.store');
    Route::put('/repairs/{repair}', [RepairController::class, 'update'])->name('repairs.update');
    Route::delete('/repairs/{repair}', [RepairController::class, 'destroy'])->name('repairs.destroy');
    Route::put('/repairs/{repair}/pay', [RepairController::class, 'markPaid'])->name('repairs.pay');
    Route::get('/repairs/{repair}/receipt', [RepairController::class, 'receipt'])->name('repairs.receipt');
});
