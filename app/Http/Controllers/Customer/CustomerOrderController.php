<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\RefundRequest;
use App\Models\User;
use App\Notifications\StatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class CustomerOrderController extends Controller
{
    public function cancel(Request $request, $id)
    {
        $user = auth()->user();
        $sale = Sale::findOrFail($id);

        if ($sale->customer_id !== $user->customer?->id) {
            abort(403, 'Unauthorized action.');
        }

        if ($sale->status !== 'pending') {
            return back()->with('error', 'Only pending orders can be cancelled. If you have already picked up the item, please request a refund instead.');
        }

        // Restore stock
        if ($sale->item) {
            $sale->item->increment('quantity', $sale->quantity);
        }

        $sale->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->input('reason', 'Customer changed their mind.'),
        ]);

        $owners = User::owners();
        Notification::send($owners, new StatusUpdated(
            'Order Cancelled',
            "Customer {$user->name} has cancelled their order for {$sale->item?->part_name}.",
            "/sales"
        ));

        return back()->with('success', 'Order cancelled successfully.');
    }

    public function requestRefund(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
            'details' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
        ]);

        $user = auth()->user();
        $sale = Sale::findOrFail($id);

        if ($sale->customer_id !== $user->customer?->id) {
            abort(403, 'Unauthorized action.');
        }

        if ($sale->status !== 'completed') {
            return back()->with('error', 'You can only request a refund for completed orders. If you have not picked it up yet, please cancel the order instead.');
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('refunds', 'public');
        }

        RefundRequest::create([
            'sale_id' => $sale->id,
            'customer_id' => $user->customer->id,
            'reason' => $request->reason,
            'details' => $request->details,
            'photo_path' => $photoPath,
            'status' => 'pending'
        ]);

        $sale->update(['status' => 'refund_requested']);

        $owners = User::owners();
        Notification::send($owners, new StatusUpdated(
            'Refund Requested',
            "Customer {$user->name} has requested a refund for {$sale->item?->part_name}.",
            "/sales"
        ));

        return back()->with('success', 'Refund request submitted successfully. We will review it shortly.');
    }
}
