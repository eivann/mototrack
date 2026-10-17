<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Sale;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    /**
     * Reason a release attempt was refused, or null on success.
     *
     * @var array{message: string, http_status: int}|null
     */
    public ?array $lastError = null;

    /**
     * Release a whole reservation (one checkout reference) atomically.
     *
     * A checkout spanning several `sales` rows sharing a transaction_reference is
     * a single reservation: it is cancelled all-or-nothing. Every line is locked
     * in deterministic id order, validated before any stock moves, and the
     * inventory rows are locked in ascending item-id order so concurrent
     * cancellation/expiry/completion cannot deadlock or double-release.
     *
     * @param  Sale       $sale   Any line belonging to the reservation.
     * @param  int|null   $customerId  Ownership scope; null skips the check (system expiry).
     * @return bool       True when the reservation was released.
     */
    public function releaseGroup(Sale $sale, string $reason, string $status = OrderStatus::SALE_CANCELLED, ?int $customerId = null): bool
    {
        $this->lastError = null;
        $customer = null;
        $reference = $sale->transaction_reference;

        $released = DB::transaction(function () use ($sale, $reason, $status, $customerId, $reference, &$customer) {
            // Pass 1: lock every line of this reference in deterministic order.
            $lines = Sale::query()
                ->where('transaction_reference', $reference)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($lines->isEmpty()) {
                $this->lastError = ['message' => 'Reservation not found.', 'http_status' => 404];
                return false;
            }

            // Ownership is verified against the locked rows, not the pre-lock model,
            // so a guessed id from another customer cannot pass.
            if ($customerId !== null && $lines->contains(fn (Sale $line) => $line->customer_id !== $customerId)) {
                $this->lastError = ['message' => 'Unauthorized action.', 'http_status' => 403];
                return false;
            }

            // Pass 2: validate EVERY line before mutating anything.
            foreach ($lines as $line) {
                if ($line->status !== OrderStatus::SALE_PENDING) {
                    $this->lastError = [
                        'message' => 'This reservation has already been processed and can no longer be cancelled.',
                        'http_status' => 409,
                    ];
                    return false;
                }

                if ($line->picked_up_at !== null) {
                    $this->lastError = ['message' => 'This reservation has already been collected.', 'http_status' => 409];
                    return false;
                }

                if ($line->payment_confirmed_at !== null) {
                    $this->lastError = [
                        'message' => 'This reservation has already been paid. Returns must be processed at the shop counter.',
                        'http_status' => 409,
                    ];
                    return false;
                }

                $deadline = $line->expires_at ?? ($line->reserved_at ? $line->reserved_at->copy()->addHours(24) : null);

                // The expiry sweep is the one caller that must be allowed to
                // act on a lapsed deadline; every other path treats a passed
                // deadline as already settled.
                if ($status !== OrderStatus::SALE_EXPIRED && $deadline && now()->greaterThan($deadline)) {
                    $this->lastError = ['message' => 'This reservation has already expired.', 'http_status' => 409];
                    return false;
                }
            }

            // Pass 3: lock stock in ascending item-id order, then restore exactly once.
            $quantities = $lines
                ->filter(fn (Sale $line) => $line->inventory_item_id !== null)
                ->groupBy('inventory_item_id')
                ->map(fn ($group) => (int) $group->sum('quantity'));

            foreach ($quantities->keys()->sort()->values() as $itemId) {
                $inventory = InventoryItem::lockForUpdate()->find($itemId);

                if ($inventory) {
                    $inventory->increment('quantity', (int) $quantities[$itemId]);
                    \App\Services\LowStockAlertService::checkAfterCommit($inventory->id);
                }
            }

            // Pass 4: persist every status transition.
            foreach ($lines as $line) {
                $line->update([
                    'status' => $status,
                    'cancellation_reason' => $reason,
                ]);
            }

            $customer = $lines->first()->customer;

            return true;
        });

        if ($released) {
            $this->notifyRelease($customer, $reference, $reason, $status);
        }

        return $released;
    }

    /**
     * Cancel an active reservation and restore its stock exactly once.
     *
     * Thin wrapper over releaseGroup() so the legacy single-row call site and the
     * grouped customer endpoint can never diverge in their rules.
     */
    public function release(Sale $sale, string $reason, string $status = OrderStatus::SALE_CANCELLED): bool
    {
        return $this->releaseGroup($sale, $reason, $status);
    }

    /**
     * Send customer and owner notices after the transaction has committed, so a
     * delivery failure can never roll back a completed cancellation.
     */
    private function notifyRelease(?\App\Models\Customer $customer, string $reference, string $reason, string $status): void
    {
        $title = $status === OrderStatus::SALE_EXPIRED ? 'Reservation Expired' : 'Reservation Cancelled';
        $message = "Reservation #{$reference} has been {$status}. Reason: " . rtrim(trim($reason), '.') . '.';

        // Named routes: the admin dashboard lives at /dashboard, so a hardcoded
        // '/admin/dashboard' path produced a broken "View Details" redirect.
        $customerLink = route('customer.dashboard', ['tab' => 'reservations']) . '#reservation-' . $reference;
        $ownerLink = route('dashboard', ['tab' => 'reservations']);

        try {
            $dispatcher = app(NotificationDispatcher::class);

            if ($customer && $customer->user) {
                $dispatcher->send($customer->user, new \App\Notifications\StatusUpdated($title, $message, $customerLink));
            }

            // The stock and status changes are already committed; a failed notice
            // must not be reported as a failed cancellation (the dispatcher isolates errors).
            $dispatcher->send(\App\Models\User::owners(), new \App\Notifications\StatusUpdated($title, $message, $ownerLink));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Reservation {$reference} released but notification failed: " . $e->getMessage());
        }
    }
}