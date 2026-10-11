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

        $reason = $request->input('reason', 'Customer changed their mind.');
        
        $success = app(\App\Services\ReservationService::class)->release($sale, $reason);

        if (!$success) {
            return back()->with('error', 'Only pending orders can be cancelled or this order was already processed. If you have already picked up the item, please request a refund instead.');
        }

        $owners = User::owners();
        Notification::send($owners, new StatusUpdated(
            'Order Cancelled',
            "Customer {$user->name} has cancelled their order for {$sale->item?->part_name}.",
            "/dashboard?tab=sales"
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
        $success = \Illuminate\Support\Facades\DB::transaction(function () use ($id, $user, $request, &$photoPath, &$qrCodePath, &$sale) {
            $sale = Sale::lockForUpdate()->findOrFail($id);
            
            if ($sale->customer_id !== $user->customer?->id) {
                return false; // Unauthorized handled outside
            }

            // Audit Item #39: Refunds only allowed if NOT picked up yet
            if (!$sale->canBeRefunded()) {
                return false;
            }

            // Prevent multiple pending refunds
            if (RefundRequest::where('sale_id', $sale->id)->whereIn('status', [OrderStatus::REFUND_PENDING, OrderStatus::REFUND_APPROVED])->exists()) {
                return false;
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
            
            return true;
        });

        if (!$success) {
            return back()->with('error', 'This order cannot be refunded right now. It may have already been refunded, picked up, or you are unauthorized.');
        }

        $methodLabel = $request->refund_method === OrderStatus::METHOD_DIGITAL ? 'Digital (QR)' : 'On-site Pickup';
        $owners = User::owners();
        Notification::send($owners, new StatusUpdated(
            'Refund Requested',
            "Customer {$user->name} has requested a {$methodLabel} refund for {$sale->item?->part_name}.",
            "/dashboard?tab=refunds"
        ));

        return back()->with('success', 'Refund request submitted successfully. We will review it shortly.');
    }
}

