<?php

namespace App\Http\Controllers\PreOrder;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PreOrder;
use App\Models\InventoryItem;

class AdminPreOrderController extends Controller
{
   public function index()
{
    $preorders  = PreOrder::with('customer')->latest()->paginate(10);
    return view('preorders.admin_index', compact('preorders'));
}

    public function pay(Request $request, PreOrder $preorder)
    {
        $error = null;
        $tendered = (float) $request->input('amount_tendered');

        DB::transaction(function () use ($preorder, $tendered, &$error) {
            $lockedPreorder = PreOrder::lockForUpdate()->findOrFail($preorder->id);
            if ($lockedPreorder->status !== 'pending') {
                $error = 'Pre-order is not eligible for payment.';
                return;
            }
            if ($lockedPreorder->is_paid) {
                $error = 'This pre-order is already paid.';
                return;
            }
            if ((float) $lockedPreorder->total <= 0 || $tendered < (float) $lockedPreorder->total) {
                $error = 'Full cash payment is required.';
                return;
            }

            $lockedPreorder->is_paid = true;
            $lockedPreorder->save();

            \App\Models\Sale::create([
                'inventory_item_id' => $lockedPreorder->inventory_item_id,
                'quantity' => $lockedPreorder->quantity,
                'price' => $lockedPreorder->price,
                'total_price' => $lockedPreorder->total,
                'amount_tendered' => $tendered,
                'change' => round($tendered - (float)$lockedPreorder->total, 2),
                'status' => 'completed',
                'customer_id' => $lockedPreorder->customer_id,
                'payment_confirmed_at' => now(),
                'transaction_reference' => 'PRE-' . strtoupper(\Illuminate\Support\Str::random(8)),
            ]);
        });

        if ($error) {
            return back()->with('error', $error);
        }



        // Payment is already committed; a notification problem must never turn it into an error page.
        app(\App\Services\NotificationDispatcher::class)->send(
            $preorder->customer?->user,
            new \App\Notifications\StatusUpdated(
                'Pre-order Payment Received',
                "Your payment of ₱" . number_format($preorder->total, 2) . " for your pre-order ({$preorder->part_name}) has been confirmed.",
                $preorder->customerLink()
            )
        );

        return back()->with('success', 'Payment recorded successfully!');
    }

    /**
     * Mark a pending pre-order as sourced (ordered from supplier / awaiting arrival).
     * Idempotent and inventory-neutral: it only stamps the sourced_at milestone.
     */
    public function source(PreOrder $preorder)
    {
        $error = null;

        DB::transaction(function () use ($preorder, &$error) {
            $order = PreOrder::lockForUpdate()->findOrFail($preorder->id);

            if ($order->status !== 'pending' || $order->is_stocked) {
                $error = 'Only pre-orders still awaiting sourcing can be marked as sourced.';
                return;
            }
            if ($order->sourced_at) {
                $error = 'This pre-order is already marked as sourced.';
                return;
            }

            $order->sourced_at = now();
            $order->save();
        });

        if ($error) {
            return back()->with('error', $error);
        }

        return back()->with('success', 'Pre-order marked as sourced. Awaiting arrival.');
    }

    /**
     * Single cash action for pickup: record the cash sale (unless the
     * pre-order was already paid before this release) and complete it in one
     * transaction. Row-locked and idempotent, so a double submit or a second
     * tab can never create a second sale or complete twice.
     */
    public function pickup(Request $request, PreOrder $preorder)
    {
        $error = null;
        $info = null;
        $tendered = (float) $request->input('amount_tendered');

        DB::transaction(function () use ($preorder, $tendered, &$error, &$info) {
            $order = PreOrder::lockForUpdate()->findOrFail($preorder->id);

            if ($order->status === 'completed') {
                $info = 'This pre-order was already picked up. No additional payment was recorded.';
                return;
            }
            if ($order->status !== 'pending') {
                $error = 'This pre-order is ' . $order->stage()['label'] . ' and can no longer be picked up.';
                return;
            }
            if (!$order->is_stocked) {
                $error = 'The part has not arrived yet. Mark it as arrived before pickup.';
                return;
            }
            if ($order->pickup_expires_at && now()->greaterThanOrEqualTo($order->pickup_expires_at)) {
                $error = 'The 24-hour pickup deadline has passed. This pre-order can no longer be picked up.';
                return;
            }

            if (!$order->is_paid) {
                if ((float) $order->total <= 0 || $tendered < (float) $order->total) {
                    $error = 'Full cash payment is required.';
                    return;
                }

                \App\Models\Sale::create([
                    'inventory_item_id' => $order->inventory_item_id,
                    'quantity' => $order->quantity,
                    'price' => $order->price,
                    'total_price' => $order->total,
                    'amount_tendered' => $tendered,
                    'change' => round($tendered - (float) $order->total, 2),
                    'status' => 'completed',
                    'customer_id' => $order->customer_id,
                    'payment_confirmed_at' => now(),
                    'picked_up_at' => now(),
                    'transaction_reference' => 'PRE-' . strtoupper(\Illuminate\Support\Str::random(8)),
                ]);
                $order->is_paid = true;
            }
            // Already-paid (legacy) pre-orders skip the sale: they were charged
            // earlier through the old "Collect payment" step.

            $order->picked_up_at = now();
            $order->status = 'completed';
            $order->save();
        });

        if ($error) {
            return back()->with('error', $error);
        }
        if ($info) {
            return back()->with('warning', $info);
        }

        app(\App\Services\NotificationDispatcher::class)->send(
            $preorder->customer?->user,
            new \App\Notifications\StatusUpdated(
                'Pre-order Picked Up',
                "Your pre-order ({$preorder->part_name}) has been picked up and paid. Thank you!",
                $preorder->customerLink()
            )
        );

        return back()->with('success', 'Pickup confirmed and cash payment recorded.');
    }

    public function stock(PreOrder $preorder)
    {
        $alreadyStocked = false;
        $order = null;
        DB::transaction(function () use ($preorder, &$alreadyStocked, &$order) {
            $order = PreOrder::lockForUpdate()->findOrFail($preorder->id);
            if ($order->status !== 'pending' || $order->is_stocked) {
                $alreadyStocked = true;
                return;
            }
            if ($order->inventory_item_id) {
                $item = InventoryItem::lockForUpdate()->findOrFail($order->inventory_item_id);
                if ($item->quantity < $order->quantity) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'quantity' => 'Insufficient available stock to allocate.',
                    ]);
                }
                $item->decrement('quantity', $order->quantity);
                \App\Services\LowStockAlertService::checkAfterCommit($item->id);
            }
            $order->is_stocked = true;
            // Arrival implies sourcing; legacy rows that skipped "Mark sourced" get the milestone here.
            $order->sourced_at = $order->sourced_at ?? now();
            $order->save();
        });

        if ($alreadyStocked) {
            return back()->with('error', 'Pre-order cannot be allocated again.');
        }

        $notified = false;
        if ($order && $order->customer && $order->customer->user) {
            $now = now();
            $expiresAt = $now->copy()->addHours(24);
            $deadlineStr = $expiresAt->format('M d, Y h:i A');

            // The 24-hour pickup deadline starts only once the readiness
            // notification row is durably recorded.
            $recorded = app(\App\Services\NotificationDispatcher::class)->send(
                $order->customer->user,
                new \App\Notifications\StatusUpdated(
                    'Pre-order Arrived — Ready for Pickup',
                    "Your pre-order for {$order->part_name} has arrived at the store! Please pick up and pay within 24 hours (Deadline: {$deadlineStr}).",
                    $order->customerLink()
                )
            );

            if ($recorded > 0) {
                $order->ready_notified_at = $now;
                $order->pickup_expires_at = $expiresAt;
                $order->save();
                $notified = true;
            } else {
                \Illuminate\Support\Facades\Log::error("Readiness notification for pre-order #{$order->id} was not recorded; pickup deadline not started.");
            }
        }

        if ($notified) {
            return back()->with('success', 'Pre-order marked as stocked! 24-hour pickup deadline started.');
        }

        return back()->with('warning', 'Pre-order marked as stocked, but customer readiness notification could not be delivered immediately. 24-hour collection deadline will start when notification is delivered.');
    }

    public function resendReadyNotification(PreOrder $preorder)
    {
        if (!$preorder->is_stocked || $preorder->status !== 'pending') {
            return back()->with('error', 'Pre-order must be in stocked pending state.');
        }

        if (!$preorder->customer || !$preorder->customer->user) {
            return back()->with('error', 'Customer user account not found.');
        }

        try {
            $now = now();
            // Safe deadline handling: Preserve existing deadline on retries; only initialize if never set
            $isFirstNotification = empty($preorder->pickup_expires_at);
            $expiresAt = $isFirstNotification ? $now->copy()->addHours(24) : $preorder->pickup_expires_at;
            $deadlineStr = $expiresAt->format('M d, Y h:i A');

            $recorded = app(\App\Services\NotificationDispatcher::class)->send(
                $preorder->customer->user,
                new \App\Notifications\StatusUpdated(
                    'Pre-order Arrived — Ready for Pickup',
                    "Your pre-order for {$preorder->part_name} has arrived at the store! Please pick up and pay within 24 hours (Deadline: {$deadlineStr}).",
                    $preorder->customerLink()
                )
            );

            if ($recorded < 1) {
                return back()->with('error', 'Failed to deliver notification. Please try again.');
            }

            if ($isFirstNotification) {
                $preorder->ready_notified_at = $now;
                $preorder->pickup_expires_at = $expiresAt;
            }
            $preorder->save();

            $msg = $isFirstNotification
                ? 'Readiness notification sent and 24-hour pickup deadline activated.'
                : 'Readiness notification resent. Existing 24-hour pickup deadline preserved.';

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Failed to resend readiness notification for pre-order #{$preorder->id}: " . $e->getMessage());
            return back()->with('error', 'Failed to deliver notification. Please try again.');
        }
    }

    public function complete(PreOrder $preorder)
    {
        $error = null;
        DB::transaction(function () use ($preorder, &$error) {
            $order = PreOrder::lockForUpdate()->findOrFail($preorder->id);
            if ($order->status !== 'pending') {
                $error = 'Only pending pre-orders can be completed.';
                return;
            }
            if (!$order->is_paid || !$order->is_stocked) {
                $error = 'Pre-order must be paid and stocked before completion.';
                return;
            }
            $order->picked_up_at = now();
            $order->status = 'completed';
            $order->save();
        });

        if ($error) return back()->with('error', $error);

        return back()->with('success', 'Pre-order completed and picked up!');
    }

    public function cancel(PreOrder $preorder)
    {
        // Delegated to the shared service so the admin and customer paths cannot
        // diverge on stock release or eligibility rules.
        $cancelled = app(\App\Services\PreOrderCancellationService::class)->cancelAsOwner($preorder);

        if (!$cancelled) {
            return back()->with('error', 'This pre-order can no longer be cancelled.');
        }

        return back()->with('success', 'Pre-order cancelled!');
    }

public function destroy(PreOrder $preorder)
{
    $preorder->delete();

    return redirect()->back()->with('success', 'Pre-order deleted successfully.');
}

}
