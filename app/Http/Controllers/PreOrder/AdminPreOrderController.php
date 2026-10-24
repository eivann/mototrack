<?php

namespace App\Http\Controllers\PreOrder;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PreOrder;
use App\Models\InventoryItem;

class AdminPreOrderController extends Controller
{
   public function index(Request $request)
{
    // SYS4-03 B2b: optional search (number / customer / part / model) and derived-stage filter.
    $search = trim((string) $request->query('q', ''));
    $stage = (string) $request->query('stage', '');
    $stage = array_key_exists($stage, PreOrder::STAGES) ? $stage : '';

    $query = PreOrder::with('customer')->latest()->orderByDesc('id');

    $terms = \App\Services\InventorySearchService::terms($search);
    if ($terms !== []) {
        $query->adminSearch($terms);
    }
    if ($stage !== '') {
        $query->inStage($stage);
    }

    $preorders = $query->paginate(10)->withQueryString();
    $filtered = $terms !== [] || $stage !== '';

    return view('preorders.admin_index', compact('preorders', 'search', 'stage', 'filtered'));
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
            // An unpaid pre-order must never carry a payment link: refuse before anything is written
            // and never overwrite the existing link.
            if ($lockedPreorder->sale_header_id !== null) {
                $error = 'This pre-order\'s payment record needs review before payment can be taken.';
                return;
            }
            if ((float) $lockedPreorder->total <= 0 || $tendered < (float) $lockedPreorder->total) {
                $error = 'Full cash payment is required.';
                return;
            }

            // SYS4-06 D2B/D2D: header + sale are created first, then the pre-order is marked paid AND
            // linked to that exact header in one save, all inside this transaction.
            $sale = app(\App\Actions\RecordHeaderedSale::class)->execute(
                \App\Models\SaleHeader::TYPE_PREORDER, 'PRE-',
                [
                    'inventory_item_id' => $lockedPreorder->inventory_item_id,
                    'quantity' => $lockedPreorder->quantity,
                    'price' => $lockedPreorder->price,
                    'total_price' => $lockedPreorder->total,
                    'customer_id' => $lockedPreorder->customer_id,
                ],
                $tendered
            );

            $lockedPreorder->is_paid = true;
            $lockedPreorder->sale_header_id = $sale->sale_header_id;
            $lockedPreorder->save();
        });

        if ($error) {
            return back()->with('error', $error);
        }



        // Payment is already committed; a notification problem must never turn it into an error page.
        app(\App\Services\NotificationDispatcher::class)->send(
            $preorder->customer?->user,
            new \App\Notifications\StatusUpdated(
                'Pre-order Payment Received',
                "Your payment of ₱" . number_format($preorder->total, 2) . " for your pre-order ({$preorder->part_name}) has been confirmed.",
                $preorder->customerLink()
            )
        );

        return back()->with('success', 'Payment recorded successfully!');
    }

    /**
     * Mark a pending pre-order as sourced (ordered from supplier / awaiting arrival).
     * Idempotent and inventory-neutral: it only stamps the sourced_at milestone.
     */
    public function source(PreOrder $preorder)
    {
        $error = null;

        DB::transaction(function () use ($preorder, &$error) {
            $order = PreOrder::lockForUpdate()->findOrFail($preorder->id);

            if ($order->status !== 'pending' || $order->is_stocked) {
                $error = 'Only pre-orders still awaiting sourcing can be marked as sourced.';
                return;
            }
            if ($order->sourced_at) {
                $error = 'This pre-order is already marked as sourced.';
                return;
            }

            $order->sourced_at = now();
            $order->save();
        });

        if ($error) {
            return back()->with('error', $error);
        }

        return back()->with('success', 'Pre-order marked as sourced. Awaiting arrival.');
    }

    /**
     * Single cash action for pickup: record the cash sale (unless the
     * pre-order was already paid before this release) and complete it in one
     * transaction. Row-locked and idempotent, so a double submit or a second
     * tab can never create a second sale or complete twice.
     */
    public function pickup(Request $request, PreOrder $preorder)
    {
        $error = null;
        $info = null;
        $tendered = (float) $request->input('amount_tendered');

        DB::transaction(function () use ($preorder, $tendered, &$error, &$info) {
            $order = PreOrder::lockForUpdate()->findOrFail($preorder->id);

            if ($order->status === 'completed') {
                $info = 'This pre-order was already picked up. No additional payment was recorded.';
                return;
            }
            if ($order->status !== 'pending') {
                $error = 'This pre-order is ' . $order->stage()['label'] . ' and can no longer be picked up.';
                return;
            }
            if (!$order->is_stocked) {
                $error = 'The part has not arrived yet. Mark it as arrived before pickup.';
                return;
            }
            if ($order->pickup_expires_at && now()->greaterThanOrEqualTo($order->pickup_expires_at)) {
                $error = 'The 24-hour pickup deadline has passed. This pre-order can no longer be picked up.';
                return;
            }

            $pickedAt = now();

            if (!$order->is_paid) {
                // Unpaid yet already linked is inconsistent: never create a replacement payment.
                if ($order->sale_header_id !== null) {
                    $error = 'This pre-order\'s payment record needs review before payment can be taken.';
                    return;
                }
                if ((float) $order->total <= 0 || $tendered < (float) $order->total) {
                    $error = 'Full cash payment is required.';
                    return;
                }

                $sale = app(\App\Actions\RecordHeaderedSale::class)->execute(
                    \App\Models\SaleHeader::TYPE_PREORDER, 'PRE-',
                    [
                        'inventory_item_id' => $order->inventory_item_id,
                        'quantity' => $order->quantity,
                        'price' => $order->price,
                        'total_price' => $order->total,
                        'customer_id' => $order->customer_id,
                    ],
                    $tendered,
                    true
                );
                $order->is_paid = true;
                $order->sale_header_id = $sale->sale_header_id;
                $pickedAt = $sale->picked_up_at; // header, sale and pre-order share this exact instant
            } else {
                // Paid earlier: sync pickup onto the linked sale/header (no-op for unlinked legacy rows).
                if ($problem = $this->syncLinkedPickup($order, $pickedAt)) {
                    $error = $problem;
                    return;
                }
            }
            // Already-paid (legacy) pre-orders skip the sale: they were charged
            // earlier through the old "Collect payment" step.

            $order->picked_up_at = $pickedAt;
            $order->status = 'completed';
            $order->save();
        });

        if ($error) {
            return back()->with('error', $error);
        }
        if ($info) {
            return back()->with('warning', $info);
        }

        app(\App\Services\NotificationDispatcher::class)->send(
            $preorder->customer?->user,
            new \App\Notifications\StatusUpdated(
                'Pre-order Picked Up',
                "Your pre-order ({$preorder->part_name}) has been picked up and paid. Thank you!",
                $preorder->customerLink()
            )
        );

        return back()->with('success', 'Pickup confirmed and cash payment recorded.');
    }

    public function stock(PreOrder $preorder)
    {
        $alreadyStocked = false;
        $order = null;
        DB::transaction(function () use ($preorder, &$alreadyStocked, &$order) {
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
                \App\Services\LowStockAlertService::checkAfterCommit($item->id);
            }
            $order->is_stocked = true;
            // Arrival implies sourcing; legacy rows that skipped "Mark sourced" get the milestone here.
            $order->sourced_at = $order->sourced_at ?? now();
            $order->save();
        });

        if ($alreadyStocked) {
            return back()->with('error', 'Pre-order cannot be allocated again.');
        }

        $notified = false;
        if ($order && $order->customer && $order->customer->user) {
            $now = now();
            $expiresAt = $now->copy()->addHours(24);
            $deadlineStr = $expiresAt->format('M d, Y h:i A');

            // The 24-hour pickup deadline starts only once the readiness
            // notification row is durably recorded.
            $recorded = app(\App\Services\NotificationDispatcher::class)->send(
                $order->customer->user,
                new \App\Notifications\StatusUpdated(
                    'Pre-order Arrived — Ready for Pickup',
                    "Your pre-order for {$order->part_name} has arrived at the store! Please pick up and pay within 24 hours (Deadline: {$deadlineStr}).",
                    $order->customerLink()
                )
            );

            if ($recorded > 0) {
                $order->ready_notified_at = $now;
                $order->pickup_expires_at = $expiresAt;
                $order->save();
                $notified = true;
            } else {
                \Illuminate\Support\Facades\Log::error("Readiness notification for pre-order #{$order->id} was not recorded; pickup deadline not started.");
            }
        }

        if ($notified) {
            return back()->with('success', 'Pre-order marked as stocked! 24-hour pickup deadline started.');
        }

        return back()->with('warning', 'Pre-order marked as stocked, but customer readiness notification could not be delivered immediately. 24-hour collection deadline will start when notification is delivered.');
    }

    public function resendReadyNotification(PreOrder $preorder)
    {
        if (!$preorder->is_stocked || $preorder->status !== 'pending') {
            return back()->with('error', 'Pre-order must be in stocked pending state.');
        }

        if (!$preorder->customer || !$preorder->customer->user) {
            return back()->with('error', 'Customer user account not found.');
        }

        try {
            $now = now();
            // Safe deadline handling: Preserve existing deadline on retries; only initialize if never set
            $isFirstNotification = empty($preorder->pickup_expires_at);
            $expiresAt = $isFirstNotification ? $now->copy()->addHours(24) : $preorder->pickup_expires_at;
            $deadlineStr = $expiresAt->format('M d, Y h:i A');

            $recorded = app(\App\Services\NotificationDispatcher::class)->send(
                $preorder->customer->user,
                new \App\Notifications\StatusUpdated(
                    'Pre-order Arrived — Ready for Pickup',
                    "Your pre-order for {$preorder->part_name} has arrived at the store! Please pick up and pay within 24 hours (Deadline: {$deadlineStr}).",
                    $preorder->customerLink()
                )
            );

            if ($recorded < 1) {
                return back()->with('error', 'Failed to deliver notification. Please try again.');
            }

            if ($isFirstNotification) {
                $preorder->ready_notified_at = $now;
                $preorder->pickup_expires_at = $expiresAt;
            }
            $preorder->save();

            $msg = $isFirstNotification
                ? 'Readiness notification sent and 24-hour pickup deadline activated.'
                : 'Readiness notification resent. Existing 24-hour pickup deadline preserved.';

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Failed to resend readiness notification for pre-order #{$preorder->id}: " . $e->getMessage());
            return back()->with('error', 'Failed to deliver notification. Please try again.');
        }
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
            $pickedAt = now();
            if ($problem = $this->syncLinkedPickup($order, $pickedAt)) {
                $error = $problem;
                return;
            }
            $order->picked_up_at = $pickedAt;
            $order->status = 'completed';
            $order->save();
        });

        if ($error) return back()->with('error', $error);

        return back()->with('success', 'Pre-order completed and picked up!');
    }

    /**
     * SYS4-06 D2D: stamp the pickup time on the pre-order's linked sale line and header, using the
     * SAME instant as the pre-order. Only the verified sale_header_id link is used (never customer,
     * item, reference or time), so unlinked legacy pre-orders are untouched (returns null, no-op).
     *
     * Fails closed: the linked records are locked and validated BEFORE any write, and a problem
     * returns a non-sensitive message with nothing changed. Must run inside the caller's transaction
     * with the pre-order already locked. Lock order: pre-order, sale line(s) by id, header (the same
     * line-then-header order as SaleController::complete). Payment time, total, tender and change are
     * never touched.
     *
     * @return string|null  null on success (or nothing to do), otherwise the refusal message
     */
    private function syncLinkedPickup(PreOrder $order, \Carbon\CarbonInterface $at): ?string
    {
        if (!$order->sale_header_id) {
            return null;
        }

        $review = 'This pre-order\'s payment record needs review before it can be collected.';

        $lines = \App\Models\Sale::where('sale_header_id', $order->sale_header_id)
            ->orderBy('id')->lockForUpdate()->get();
        $header = \App\Models\SaleHeader::lockForUpdate()->find($order->sale_header_id);

        if (!$header || $lines->count() !== 1) {
            return $review;
        }
        $line = $lines->first();

        $consistent = $header->transaction_type === \App\Models\SaleHeader::TYPE_PREORDER
            && $header->payment_confirmed_at !== null
            && $line->transaction_reference === $header->transaction_reference
            && $line->status === 'completed'
            && $line->payment_confirmed_at !== null
            && $line->customer_id === $header->customer_id
            && $line->customer_id === $order->customer_id
            && $line->inventory_item_id === $order->inventory_item_id
            && (int) $line->quantity === (int) $order->quantity
            && abs((float) $line->total_price - (float) $order->total) < 0.005
            && abs((float) $header->total_amount - (float) $order->total) < 0.005
            // A still-uncollected pre-order cannot already have a pickup recorded on its sale/header.
            && $line->picked_up_at === null
            && $header->picked_up_at === null;

        if (!$consistent) {
            return $review;
        }

        $line->update(['picked_up_at' => $at]);
        $header->update(['picked_up_at' => $at]);

        return null;
    }

    public function cancel(PreOrder $preorder)
    {
        // Delegated to the shared service so the admin and customer paths cannot
        // diverge on stock release or eligibility rules.
        $cancelled = app(\App\Services\PreOrderCancellationService::class)->cancelAsOwner($preorder);

        if (!$cancelled) {
            return back()->with('error', 'This pre-order can no longer be cancelled.');
        }

        return back()->with('success', 'Pre-order cancelled!');
    }

public function destroy(PreOrder $preorder)
{
    $preorder->delete();

    return redirect()->back()->with('success', 'Pre-order deleted successfully.');
}

}
