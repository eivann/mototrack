<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Enums\OrderStatus;
use App\Models\Sale;
use App\Models\Customer;
use App\Services\ReservationService;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    public function __construct(protected ReservationService $reservations)
    {
    }

    /**
     * Cancel the whole reservation that $id belongs to.
     *
     * Reached from both the legacy per-item button and the grouped My Reservations
     * panel; both resolve to the same transaction_reference and therefore follow
     * identical rules. A multi-item reservation is never partially cancelled.
     */
    public function cancel(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user = auth()->user();
        $sale = Sale::findOrFail($id);
        $customer = Customer::firstOrCreate(
            ['user_id' => $user->id],
            ['name' => $user->name, 'email' => $user->email]
        );

        // Cheap pre-check to avoid leaking existence details in the common case;
        // the authoritative check runs again against the locked rows.
        if ($sale->customer_id !== $customer->id) {
            abort(403, 'Unauthorized action.');
        }

        $reason = $validated['reason'] ?? 'Customer changed their mind.';

        $success = $this->reservations->releaseGroup(
            $sale,
            $reason,
            OrderStatus::SALE_CANCELLED,
            $customer->id
        );

        if (!$success) {
            $error = $this->reservations->lastError;

            if ($error && $error['http_status'] === 403) {
                abort(403, $error['message']);
            }

            return back()->with('error', $error['message'] ?? 'This reservation can no longer be cancelled.');
        }

        return back()->with('success', 'Reservation cancelled successfully and all reserved stock has been released.');
    }

    /**
     * Cancel a reservation by its checkout reference (grouped panel entry point).
     */
    public function cancelByReference(Request $request, string $reference)
    {
        $user = auth()->user();
        $customer = Customer::firstOrCreate(
            ['user_id' => $user->id],
            ['name' => $user->name, 'email' => $user->email]
        );

        $sale = Sale::where('transaction_reference', $reference)->firstOrFail();

        if ($sale->customer_id !== $customer->id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $success = $this->reservations->releaseGroup(
            $sale,
            $validated['reason'] ?? 'Customer changed their mind.',
            OrderStatus::SALE_CANCELLED,
            $customer->id
        );

        if (!$success) {
            $error = $this->reservations->lastError;

            if ($error && $error['http_status'] === 403) {
                abort(403, $error['message']);
            }

            return back()->with('error', $error['message'] ?? 'This reservation can no longer be cancelled.');
        }

        return back()->with('success', 'Reservation cancelled successfully and all reserved stock has been released.');
    }

    public function requestRefund(Request $request, $id)
    {
        return back()->with('error', 'Online refund requests are not supported. As an in-store cash payment system, returns and refunds must be processed directly at the physical shop counter.');
    }
}