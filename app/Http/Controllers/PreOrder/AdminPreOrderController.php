<?php

namespace App\Http\Controllers\PreOrder;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PreOrder;
use App\Models\InventoryItem;

class AdminPreOrderController extends Controller
{
   public function index()
{
    $preorders  = PreOrder::latest()->paginate(10);
    return view('preorders.admin_index', compact('preorders'));
}

    public function complete(PreOrder $preorder)
    {
        $item = InventoryItem::where('part_name', $preorder->part_name)
            ->where('model', $preorder->model)
            ->first();

        if ($item) {
            $item->increment('quantity', $preorder->quantity);
        }
        $preorder->status = 'completed';
        $preorder->save();

        // Notify customer
        if ($preorder->customer && $preorder->customer->user) {
            $preorder->customer->user->notify(new \App\Notifications\StatusUpdated(
                'Pre-order Ready!',
                "Your pre-order for {$preorder->part_name} has arrived and is ready for pickup at the store!",
                '/customer-dashboard'
            ));
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
                '/customer-dashboard'
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
            '/customer-dashboard'
        ));
    }

    return back()->with('success', 'Pre-order marked as paid!');
}

}
