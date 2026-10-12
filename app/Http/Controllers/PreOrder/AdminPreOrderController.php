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



        if ($preorder->customer && $preorder->customer->user) {
            $preorder->customer->user->notify(new \App\Notifications\StatusUpdated(
                'Pre-order Payment Received',
                "Your payment of ₱" . number_format($preorder->total, 2) . " for your pre-order ({$preorder->part_name}) has been confirmed.",
                '/customer/dashboard'
            ));
        }

        return back()->with('success', 'Payment recorded successfully!');
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
            }
            $order->is_stocked = true;
            $order->save();
        });

        if ($alreadyStocked) {
            return back()->with('error', 'Pre-order cannot be allocated again.');
        }

        $notified = false;
        if ($order && $order->customer && $order->customer->user) {
            try {
                $now = now();
                $expiresAt = $now->copy()->addHours(24);
                $deadlineStr = $expiresAt->format('M d, Y h:i A');

                $order->customer->user->notify(new \App\Notifications\StatusUpdated(
                    'Pre-order Arrived — Ready for Pickup',
                    "Your pre-order for {$order->part_name} has arrived at the store! Please pick up and pay within 24 hours (Deadline: {$deadlineStr}).",
                    '/customer/dashboard'
                ));

                $order->ready_notified_at = $now;
                $order->pickup_expires_at = $expiresAt;
                $order->save();
                $notified = true;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Failed to notify customer for pre-order #{$order->id}: " . $e->getMessage());
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

            $preorder->customer->user->notify(new \App\Notifications\StatusUpdated(
                'Pre-order Arrived — Ready for Pickup',
                "Your pre-order for {$preorder->part_name} has arrived at the store! Please pick up and pay within 24 hours (Deadline: {$deadlineStr}).",
                '/customer/dashboard'
            ));

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
        $error = null;
        $order = null;
        DB::transaction(function () use ($preorder, &$error, &$order) {
            $order = PreOrder::lockForUpdate()->findOrFail($preorder->id);
            if ($order->status !== 'pending') {
                $error = 'Only pending pre-orders can be cancelled.';
                return;
            }
            if ($order->is_paid) {
                $error = 'A paid pre-order requires a separate refund workflow.';
                return;
            }
            if ($order->is_stocked) {
                if ($order->inventory_item_id) {
                    $item = InventoryItem::lockForUpdate()->find($order->inventory_item_id);
                    if ($item) {
                        $item->increment('quantity', $order->quantity);
                    }
                }
                $order->is_stocked = false;
            }
            $order->status = 'cancelled';
            $order->save();
        });

        if ($error) return back()->with('error', $error);

        // Notify customer
        if ($order && $order->customer && $order->customer->user) {
            $order->customer->user->notify(new \App\Notifications\StatusUpdated(
                'Pre-order Cancelled',
                "Your pre-order for {$order->part_name} has been cancelled.",
                '/customer/dashboard'
            ));
        }

        return back()->with('success', 'Pre-order cancelled!');
    }

public function destroy(PreOrder $preorder)
{
    $preorder->delete();

    return redirect()->back()->with('success', 'Pre-order deleted successfully.');
}

}
