<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PreOrder;
use App\Models\InventoryItem;

class PreOrderController extends Controller
{
   public function index()
{
    $preorders  = PreOrder::latest()->get();
    return view('preorders', compact('preorders'));
}

    public function complete($id)
    {
        $preorders = PreOrder::findOrFail($id);
        $item = InventoryItem::where('part_name', $preorders->part_name)
            ->where('model', $preorders->model)
            ->first();

        if ($item) {
            $item->increment('quantity', $preorders->quantity);
        }
        $preorders->status = 'completed';
        $preorders->save();

        // Notify customer
        if ($preorders->customer && $preorders->customer->user) {
            $preorders->customer->user->notify(new \App\Notifications\StatusUpdated(
                'Pre-order Ready!',
                "Your pre-order for {$preorders->part_name} has arrived and is ready for pickup at the store!",
                '/customer-dashboard'
            ));
        }

        return back()->with('success', 'Pre-order completed!');
    }

    public function cancel($id)
    {
        $preorders = PreOrder::findOrFail($id);
        $preorders->status = 'cancelled';
        $preorders->save();

        // Notify customer
        if ($preorders->customer && $preorders->customer->user) {
            $preorders->customer->user->notify(new \App\Notifications\StatusUpdated(
                'Pre-order Cancelled',
                "Your pre-order for {$preorders->part_name} has been cancelled.",
                '/customer-dashboard'
            ));
        }

        return back()->with('success', 'Pre-order cancelled!');
    }

public function destroy($id)
{
    $order = PreOrder::findOrFail($id);
    $order->delete();

    return redirect()->back()->with('success', 'Pre-order deleted successfully.');
}

public function markPaid($id)
{
    $preorder = PreOrder::findOrFail($id);
    $preorder->is_paid = true;
    $preorder->save();

    // Notify customer
    if ($preorder->customer && $preorder->customer->user) {
        $preorder->customer->user->notify(new \App\Notifications\StatusUpdated(
            'Down Payment Received',
            "Your down payment for {$preorder->part_name} has been confirmed. We are now sourcing your part!",
            '/customer-dashboard'
        ));
    }

    return back()->with('success', 'Pre-order marked as paid!');
}

}
