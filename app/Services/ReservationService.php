<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    /**
     * Cancel an active reservation and restore its stock exactly once.
     */
    public function release(Sale $sale, string $reason, string $status = 'cancelled'): bool
    {
        $shouldNotify = false;
        $customer = null;

        $released = DB::transaction(function () use ($sale, $reason, $status, &$shouldNotify, &$customer) {
            $lockedSale = Sale::lockForUpdate()->find($sale->id);

            if (!$lockedSale || $lockedSale->status !== 'pending') {
                return false;
            }

            // Restore stock exactly once
            if ($lockedSale->inventory_item_id) {
                $inventory = InventoryItem::lockForUpdate()->find($lockedSale->inventory_item_id);
                if ($inventory) {
                    $inventory->increment('quantity', $lockedSale->quantity);
                }
            }

            $lockedSale->update([
                'status' => $status,
                'cancellation_reason' => $reason,
            ]);

            $shouldNotify = true;
            $customer = $lockedSale->customer;

            return true;
        });

        if ($released && $shouldNotify) {
            $title = $status === 'expired' ? 'Reservation Expired' : 'Reservation Cancelled';
            $message = "Reservation #{$sale->transaction_reference} has been {$status}. Reason: {$reason}.";

            // Notify customer
            if ($customer && $customer->user) {
                $customer->user->notify(new \App\Notifications\StatusUpdated(
                    $title,
                    $message,
                    '/customer/dashboard'
                ));
            }

            // Notify owners
            $owners = \App\Models\User::owners();
            \Illuminate\Support\Facades\Notification::send($owners, new \App\Notifications\StatusUpdated(
                $title,
                $message,
                '/admin/dashboard?tab=reservations'
            ));
        }

        return $released;
    }
}
