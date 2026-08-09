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

    public function complete(PreOrder $preorder)
    {
        // Audit #10: restock against the linked catalog item when present.
        // Fall back to the legacy part_name + model string match only for
        // pre-orders created before the FK existed. Lock the row so concurrent
        // completions can't double-increment, and warn the admin when no
        // inventory item can be resolved rather than silently skipping restock.
        $matchedByString = false;

        DB::transaction(function () use ($preorder, &$matchedByString) {
            $item = null;

            if ($preorder->inventory_item_id) {
                $item = InventoryItem::lockForUpdate()->find($preorder->inventory_item_id);
            } elseif ($preorder->part_name) {
                $item = InventoryItem::where('part_name', $preorder->part_name)
                    ->where('model', $preorder->model)
                    ->lockForUpdate()
                    ->first();
                $matchedByString = (bool) $item;
            }

            if ($item) {
                $item->increment('quantity', $preorder->quantity);
            }

            $preorder->status = 'completed';
            $preorder->save();
        });

        // Notify customer
        if ($preorder->customer && $preorder->customer->user) {
            $preorder->customer->user->notify(new \App\Notifications\StatusUpdated(
                'Pre-order Ready!',
                "Your pre-order for {$preorder->part_name} has arrived and is ready for pickup at the store!",
                '/customer/dashboard'
            ));
        }

        // Surface a no-restock situation so the owner can adjust stock manually.
        if (!$preorder->inventory_item_id && !$matchedByString) {
            return back()->with('warning', "Pre-order completed, but no matching inventory item was found for \"{$preorder->part_name}\" — stock was not adjusted. Please update inventory manually if needed.");
        }

        return back()->with('success', 'Pre-order completed!');
    }

    public function cancel(PreOrder $preorder)
    {
        $preorder->status = 'cancelled';
        $preorder->save();

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

public function markPaid(PreOrder $preorder)
{
    $preorder->is_paid = true;
    $preorder->save();

    // Notify customer
    if ($preorder->customer && $preorder->customer->user) {
        $preorder->customer->user->notify(new \App\Notifications\StatusUpdated(
            'Down Payment Received',
            "Your down payment for {$preorder->part_name} has been confirmed. We are now sourcing your part!",
            '/customer/dashboard'
        ));
    }

    return back()->with('success', 'Pre-order marked as paid!');
}

}
