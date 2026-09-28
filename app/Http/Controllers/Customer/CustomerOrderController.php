<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Enums\OrderStatus;
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
        $rules = [
            'reason' => 'required|string|max:255',
            'details' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
            'refund_method' => 'required|in:' . implode(',', OrderStatus::refundMethods()),
        ];

        // Conditional validation based on refund method
        if ($request->input('refund_method') === OrderStatus::METHOD_DIGITAL) {
            $rules['qr_code'] = 'required|image|max:2048';
        }
        if ($request->input('refund_method') === OrderStatus::METHOD_ON_SITE) {
            $rules['pickup_time'] = 'required|date|after:now';
        }

        $request->validate($rules, [
            'qr_code.required' => 'Please upload your QR code so we can send your refund digitally.',
            'pickup_time.required' => 'Please select when you will pick up your refund.',
            'pickup_time.after' => 'The pickup time must be a future date and time.',
        ]);

        $user = auth()->user();
        $sale = Sale::findOrFail($id);

        if ($sale->customer_id !== $user->customer?->id) {
            abort(403, 'Unauthorized action.');
        }

        // Audit Item #39: Refunds only allowed if NOT picked up yet
        if (!$sale->canBeRefunded()) {
            if ($sale->isPickedUp()) {
                return back()->with('error', 'This item has already been picked up and cannot be refunded. For concerns about a picked-up item, please contact the shop directly.');
            }
            return back()->with('error', 'This order is not eligible for a refund. It may have already been refunded or is in an invalid state.');
        }

        // Handle file uploads
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('refunds', 'public');
        }

        $qrCodePath = null;
        if ($request->hasFile('qr_code')) {
            $qrCodePath = $request->file('qr_code')->store('refund_qr_codes', 'public');
        }

        RefundRequest::create([
            'sale_id' => $sale->id,
            'customer_id' => $user->customer->id,
            'reason' => $request->reason,
            'details' => $request->details,
            'photo_path' => $photoPath,
            'status' => OrderStatus::REFUND_PENDING,
            'refund_method' => $request->refund_method,
            'qr_code_path' => $qrCodePath,
            'pickup_time' => $request->pickup_time,
        ]);

        $sale->update(['status' => OrderStatus::SALE_REFUND_REQUESTED]);

        $methodLabel = $request->refund_method === OrderStatus::METHOD_DIGITAL ? 'Digital (QR)' : 'On-site Pickup';
        $owners = User::owners();
        Notification::send($owners, new StatusUpdated(
            'Refund Requested',
            "Customer {$user->name} has requested a {$methodLabel} refund for {$sale->item?->part_name}.",
            "/sales"
        ));

        return back()->with('success', 'Refund request submitted successfully. We will review it shortly.');
    }
}

