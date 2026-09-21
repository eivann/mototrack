<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RefundRequest;
use App\Models\Sale;
use App\Notifications\StatusUpdated;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function approve(Request $request, $id)
    {
        $refundRequest = RefundRequest::findOrFail($id);
        
        $refundRequest->update([
            'status' => 'approved',
            'admin_notes' => $request->admin_notes
        ]);

        $refundRequest->sale->update(['status' => 'refunded']);

        // Restore stock
        if ($refundRequest->sale->item && $request->restock) {
            $refundRequest->sale->item->increment('quantity', $refundRequest->sale->quantity);
        }

        $user = $refundRequest->customer->user;
        if ($user) {
            $user->notify(new StatusUpdated(
                'Refund Approved',
                "Your refund request for {$refundRequest->sale->item?->part_name} has been approved.",
                '/customer/dashboard'
            ));
        }

        return back()->with('success', 'Refund request approved.');
    }

    public function reject(Request $request, $id)
    {
        $refundRequest = RefundRequest::findOrFail($id);
        
        $refundRequest->update([
            'status' => 'rejected',
            'admin_notes' => $request->admin_notes
        ]);

        // Revert sale status back to completed since the refund was rejected
        $refundRequest->sale->update(['status' => 'completed']);

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
