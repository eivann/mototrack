<?php

use App\Http\Controllers\RepairReservation\CustomerRepairReservationController;
use App\Http\Controllers\RepairReservation\OwnerRepairBlackoutController;
use App\Http\Controllers\RepairReservation\OwnerRepairReservationController;
use Illuminate\Support\Facades\Route;

// RD2-R3: customer same-day repair / service requests (separate from the owner-managed /repairs module).
Route::middleware('auth')->group(function () {
    Route::get('/repair-reservations/new', [CustomerRepairReservationController::class, 'create'])->name('repair-reservations.create');
    Route::post('/repair-reservations', [CustomerRepairReservationController::class, 'store'])->middleware('throttle:10,1')->name('repair-reservations.store');
    Route::get('/repair-reservations/{reference}', [CustomerRepairReservationController::class, 'show'])->where('reference', 'RR-[A-Z0-9-]+')->name('repair-reservations.show');
    Route::post('/repair-reservations/{reference}/cancel', [CustomerRepairReservationController::class, 'cancel'])->where('reference', 'RR-[A-Z0-9-]+')->name('repair-reservations.cancel');

    // RD2-R3 Phase 4: owner-managed blackouts.
    Route::middleware('owner')->prefix('owner/repair-blackouts')->name('owner.repair-blackouts.')->group(function () {
        Route::get('/', [OwnerRepairBlackoutController::class, 'index'])->name('index');
        Route::post('/', [OwnerRepairBlackoutController::class, 'store'])->name('store');
        Route::delete('/{blackout}', [OwnerRepairBlackoutController::class, 'destroy'])->whereNumber('blackout')->name('destroy');
    });

    Route::middleware('owner')->prefix('owner/repair-reservations')->name('owner.repair-reservations.')->group(function () {
        Route::get('/', [OwnerRepairReservationController::class, 'index'])->name('index');
        Route::post('/{reservation}/accept', [OwnerRepairReservationController::class, 'accept'])->whereNumber('reservation')->name('accept');
        Route::post('/{reservation}/reject', [OwnerRepairReservationController::class, 'reject'])->whereNumber('reservation')->name('reject');
        Route::post('/{reservation}/check-in', [OwnerRepairReservationController::class, 'checkIn'])->whereNumber('reservation')->name('check-in');
        Route::post('/{reservation}/start-repair', [OwnerRepairReservationController::class, 'startRepair'])->whereNumber('reservation')->name('start-repair');
        Route::post('/{reservation}/cancel', [OwnerRepairReservationController::class, 'cancel'])->whereNumber('reservation')->name('cancel');
    });
});
