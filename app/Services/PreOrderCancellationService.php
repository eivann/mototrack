<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\InventoryItem;
use App\Models\PreOrder;
use App\Models\User;
use App\Notifications\StatusUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Single source of truth for pre-order cancellation.
 *
 * The customer dashboard and the admin panel both delegate here so their
 * eligibility rules and stock effects cannot drift apart.
 */
class PreOrderCancellationService
{
    /**
     * Reason a cancellation was refused, or null on success.
     *
     * @var array{message: string, http_status: int}|null
     */
    public ?array $lastError = null;

    /**
     * Cancel a pre-order the given customer owns.
     */
    public function cancelForCustomer(PreOrder $preorder, int $customerId, string $reason): bool
    {
        $this->lastError = null;

        // Ownership is checked before locking so a guessed id from another
        // customer cannot reach the mutation path at all.
        if ($preorder->customer_id !== $customerId) {
            $this->lastError = ['message' => 'Unauthorized action.', 'http_status' => 403];
            return false;
        }

        return $this->cancel($preorder, $reason);
    }

    /**
     * Cancel a pre-order on behalf of staff.
     */
    public function cancelAsOwner(PreOrder $preorder, string $reason = 'Cancelled by staff.'): bool
    {
        $this->lastError = null;
        return $this->cancel($preorder, $reason);
    }

    /**
     * Cancel a pre-order, releasing only stock that was actually allocated.
     *
     * Unstocked sourcing requests touch no inventory at all. Custom pre-orders
     * without a linked catalog item never invent stock. Paid or collected
     * records are refused outright.
     */
    private function cancel(PreOrder $preorder, string $reason): bool
    {
        $customerUser = null;
        $partName = $preorder->part_name;

        $cancelled = DB::transaction(function () use ($preorder, &$customerUser) {
            // Re-read under lock: state may have changed since the route model
            // binding resolved (e.g. staff stocked or paid it in the meantime).
            $order = PreOrder::lockForUpdate()->find($preorder->id);

            if (!$order) {
                $this->lastError = ['message' => 'Pre-order not found.', 'http_status' => 404];
                return false;
            }

            if (!in_array($order->status, [OrderStatus::PREORDER_PENDING, OrderStatus::PREORDER_ARRIVED], true)) {
                $this->lastError = [
                    'message' => 'This pre-order can no longer be cancelled.',
                    'http_status' => 409,
                ];
                return false;
            }

            if ($order->is_paid) {
                $this->lastError = [
                    'message' => 'A paid pre-order requires an in-store refund. Please contact the shop counter.',
                    'http_status' => 409,
                ];
                return false;
            }

            if ($order->picked_up_at !== null) {
                $this->lastError = ['message' => 'This pre-order has already been collected.', 'http_status' => 409];
                return false;
            }

            // Release only what was genuinely allocated. Unstocked sourcing
            // requests and custom parts without a catalog link move no stock.
            if ($order->is_stocked && $order->inventory_item_id) {
                $item = InventoryItem::lockForUpdate()->find($order->inventory_item_id);

                if ($item) {
                    $item->increment('quantity', $order->quantity);
                    \App\Services\LowStockAlertService::checkAfterCommit($item->id);
                }
            }

            $order->is_stocked = false;
            $order->status = OrderStatus::PREORDER_CANCELLED;
            $order->save();

            // The reason is preserved in the audit trail (HasAuditTrail on Sale;
            // PreOrder also produces an audit row for the status change). Do not
            // add a dedicated column for it.


            if ($order->customer && $order->customer->user) {
                $customerUser = $order->customer->user;
            }

            return true;
        });

        if ($cancelled) {
            $this->notify($customerUser, $order ?? $preorder, $partName, $reason);
        }

        return $cancelled;
    }

    /**
     * Notify owner and customer after the transaction has committed.
     */
    private function notify(?\App\Models\User $customerUser, PreOrder $order, ?string $partName, string $reason): void
    {
        try {
            $dispatcher = app(NotificationDispatcher::class);

            if ($customerUser) {
                $dispatcher->send($customerUser, new StatusUpdated(
                    'Pre-order Cancelled',
                    "Your pre-order for {$partName} has been cancelled. Reason: " . rtrim(trim($reason), '.') . '.',
                    route('customer.dashboard')
                ));
            }

            $dispatcher->send(User::owners(), new StatusUpdated(
                'Pre-order Cancelled',
                "Pre-order #{$order->id} ({$partName}) has been cancelled. Reason: " . rtrim(trim($reason), '.') . '.',
                route('preorders.index')
            ));
        } catch (\Throwable $e) {
            Log::warning("Pre-order #{$order->id} cancelled but notification failed: " . $e->getMessage());
        }
    }
}