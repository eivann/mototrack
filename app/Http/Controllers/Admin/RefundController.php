<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\OrderStatus;
use App\Models\RefundRequest;
use App\Models\Sale;
use App\Notifications\StatusUpdated;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    /**
     * Audit Item #32: Both approve and reject require admin_notes (reason).
     * Audit Item #44: Sales deduction only happens here on approval.
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'admin_notes' => 'required|string|max:500',
            'restock' => 'nullable|boolean',
        ], [
            'admin_notes.required' => 'Please provide a reason for approving this refund.',
        ]);

        $refundRequest = RefundRequest::findOrFail($id);
        
        $refundRequest->update([
            'status' => OrderStatus::REFUND_APPROVED,
            'admin_notes' => $request->admin_notes
        ]);

        // Audit Item #44: Only deduct from sales on confirmed approval
        $refundRequest->sale->update(['status' => OrderStatus::SALE_REFUNDED]);

        // Restore stock if requested
        if ($refundRequest->sale->item && $request->restock) {
            $refundRequest->sale->item->increment('quantity', $refundRequest->sale->quantity);
        }

        $user = $refundRequest->customer->user;
        if ($user) {
            $methodMsg = $refundRequest->isDigital()
                ? ' Your refund will be sent to the QR code you provided.'
                : ($refundRequest->isOnSite()
                    ? ' Please pick up your refund at the scheduled time.'
                    : '');

            $user->notify(new StatusUpdated(
                'Refund Approved',
                "Your refund request for {$refundRequest->sale->item?->part_name} has been approved.{$methodMsg}",
                '/customer/dashboard'
            ));
        }

        return back()->with('success', 'Refund request approved.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'admin_notes' => 'required|string|max:500',
        ], [
            'admin_notes.required' => 'Please provide a reason for rejecting this refund.',
        ]);

        $refundRequest = RefundRequest::findOrFail($id);
        
        $refundRequest->update([
            'status' => OrderStatus::REFUND_REJECTED,
            'admin_notes' => $request->admin_notes
        ]);

        // Revert sale status back to completed since the refund was rejected
        $refundRequest->sale->update(['status' => OrderStatus::SALE_COMPLETED]);

        $user = $refundRequest->customer->user;
        if ($user) {
            $user->notify(new StatusUpdated(
                'Refund Denied',
                "Your refund request for {$refundRequest->sale->item?->part_name} has been denied. Reason: " . $request->admin_notes,
                '/customer/dashboard'
            ));
        }

        return back()->with('success', 'Refund request rejected.');
    }
}

