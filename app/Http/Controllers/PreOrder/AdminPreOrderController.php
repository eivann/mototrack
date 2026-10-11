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
        DB::transaction(function () use ($preorder, &$alreadyStocked) {
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

        if ($preorder->customer && $preorder->customer->user) {
            $preorder->customer->user->notify(new \App\Notifications\StatusUpdated(
                'Pre-order Arrived',
                "Your pre-order for {$preorder->part_name} has arrived at the store!",
                '/customer/dashboard'
            ));
        }

        return back()->with('success', 'Pre-order marked as stocked!');
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
        DB::transaction(function () use ($preorder, &$error) {
            $order = PreOrder::lockForUpdate()->findOrFail($preorder->id);
            if ($order->status !== 'pending') {
                $error = 'Only pending pre-orders can be cancelled.';
                return;
            }
            if ($order->is_paid) {
                $error = 'A paid pre-order requires a separate refund workflow.';
                return;
            }
            if ($order->is_stocked && $order->inventory_item_id) {
                $item = InventoryItem::lockForUpdate()->findOrFail($order->inventory_item_id);
                $item->increment('quantity', $order->quantity);
            }
            $order->status = 'cancelled';
            $order->save();
        });

        if ($error) return back()->with('error', $error);

        // Notify customer
        if ($preorder->customer && $preorder->customer->user) {
            $preorder->customer->user->notify(new \App\Notifications\StatusUpdated(
                'Pre-order Cancelled',
                "Your pre-order for {$preorder->part_name} has been cancelled.",
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
