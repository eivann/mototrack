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

        $success = \Illuminate\Support\Facades\DB::transaction(function () use ($id, $request, &$refundRequest) {
            $refundRequest = RefundRequest::lockForUpdate()->findOrFail($id);
            if ($refundRequest->status !== OrderStatus::REFUND_PENDING) {
                return false;
            }
            
            $refundRequest->update([
                'status' => OrderStatus::REFUND_APPROVED,
                'admin_notes' => $request->admin_notes
            ]);

            // Audit Item #44: Only deduct from sales on confirmed approval
            $sale = Sale::lockForUpdate()->find($refundRequest->sale_id);
            if ($sale && $sale->status !== OrderStatus::SALE_REFUNDED) {
                $sale->update(['status' => OrderStatus::SALE_REFUNDED]);

                if ($sale->inventory_item_id) {
                    // Record auditable ProductReturn linked to the sale
                    \App\Models\ProductReturn::create([
                        'sale_id'           => $sale->id,
                        'inventory_item_id' => $sale->inventory_item_id,
                        'customer_id'       => $sale->customer_id,
                        'quantity'          => $sale->quantity,
                        'refund_amount'     => $sale->total_price,
                        'action_taken'      => $request->restock ? 'restocked' : 'discarded',
                        'notes'             => 'In-store Refund: ' . $request->admin_notes,
                    ]);

                    // Restore stock if requested, or log as damaged write-off
                    if ($request->restock) {
                        $item = \App\Models\InventoryItem::lockForUpdate()->find($sale->inventory_item_id);
                        if ($item) {
                            $item->increment('quantity', $sale->quantity);
                            \App\Services\LowStockAlertService::checkAfterCommit($item->id);
                        }
                    } else {
                        \App\Models\DamagedItem::create([
                            'inventory_item_id' => $sale->inventory_item_id,
                            'quantity'          => $sale->quantity,
                            'notes'             => 'Refund Write-Off: ' . $request->admin_notes,
                        ]);
                    }
                }
            }

            return true;
        });

        if (!$success) {
            return back()->with('error', 'Refund is already processed.');
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
                route('customer.dashboard', ['tab' => 'purchases'])
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

        $success = \Illuminate\Support\Facades\DB::transaction(function () use ($id, $request, &$refundRequest) {
            $refundRequest = RefundRequest::lockForUpdate()->findOrFail($id);
            if ($refundRequest->status !== OrderStatus::REFUND_PENDING) {
                return false;
            }

            $refundRequest->update([
                'status' => OrderStatus::REFUND_REJECTED,
                'admin_notes' => $request->admin_notes
            ]);
            
            $sale = Sale::lockForUpdate()->find($refundRequest->sale_id);
            if ($sale && $sale->status !== OrderStatus::SALE_COMPLETED) {
                $sale->update(['status' => OrderStatus::SALE_COMPLETED]);
            }

            return true;
        });

        if (!$success) {
            return back()->with('error', 'Refund is already processed.');
        }

        $user = $refundRequest->customer->user;
        if ($user) {
            $user->notify(new StatusUpdated(
                'Refund Denied',
                "Your refund request for {$refundRequest->sale->item?->part_name} has been denied. Reason: " . $request->admin_notes,
                route('customer.dashboard', ['tab' => 'purchases'])
            ));
        }

        return back()->with('success', 'Refund request rejected.');
    }
}

