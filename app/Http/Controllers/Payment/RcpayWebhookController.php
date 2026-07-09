<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\PreOrder;
use App\Models\Sale;
use App\Models\User;
use App\Notifications\StatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class RcpayWebhookController extends Controller
{
    /**
     * Handle the RCPAY PLUS webhook.
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::info('RCPAY PLUS Webhook received:', $payload);

        // Signature verified by the `verify.rcpay.signature` middleware (audit #11).

        // Only process successful payments
        if ($payload['status'] !== 'SUCCESS') {
            Log::info('RCPAY PLUS Webhook: Payment status is not SUCCESS (status: ' . $payload['status'] . ').');
            return response()->json(['message' => 'Ignored: Status is not SUCCESS'], 200);
        }

        $orderId = $payload['order_id'];
        $amount = floatval($payload['amount']);

        // Check if this is a Pre-order
        if (str_starts_with($orderId, 'PRE-')) {
            return $this->handlePreOrderPayment($orderId, $amount);
        }

        // Check if this is a Shop Order / Sale
        if (str_starts_with($orderId, 'TRX-')) {
            return $this->handleShopOrderPayment($orderId, $amount);
        }

        Log::warning("RCPAY PLUS Webhook: Order ID pattern not recognized ({$orderId}).");
        return response()->json(['message' => 'Ignored: Unrecognized order ID format'], 200);
    }

    /**
     * Process Pre-order Down Payment.
     */
    protected function handlePreOrderPayment($orderId, $amount)
    {
        // Format: PRE-{id}-{timestamp}
        $parts = explode('-', $orderId);
        if (count($parts) < 2) {
            Log::warning("RCPAY PLUS Webhook: Malformed Pre-order ID ({$orderId}).");
            return response()->json(['message' => 'Malformed Pre-order ID'], 400);
        }

        $preorderId = $parts[1];
        $preorder = PreOrder::find($preorderId);

        if (!$preorder) {
            Log::error("RCPAY PLUS Webhook: Pre-order not found with ID {$preorderId}.");
            return response()->json(['message' => 'Pre-order not found'], 404);
        }

        if ($preorder->is_paid) {
            Log::info("RCPAY PLUS Webhook: Pre-order {$preorderId} is already paid.");
            return response()->json(['message' => 'OK'], 200);
        }

        // Audit #13: resolve display data here (the pre-order is already loaded)
        // so the queued notification listener doesn't re-parse the order id or
        // re-query the model.
        $customerUser = $preorder->customer ? $preorder->customer->user : null;
        $context = [
            'customer_user_id' => $customerUser?->id,
            'customer_name' => $customerUser?->name ?? $preorder->customer_name,
            'display_amount' => (float) $preorder->down_payment,
            'part_name' => $preorder->part_name,
        ];

        // Fire Domain Event to decouple Database and Notification processing
        \App\Events\PaymentReceived::dispatch('preorder', $orderId, $amount, $context);

        return response()->json(['message' => 'OK'], 200);
    }

    /**
     * Process Shop Order Payment.
     */
    protected function handleShopOrderPayment($orderId, $amount)
    {
        // F-1: Lightweight existence check without eager-loading relations.
        if (!Sale::where('transaction_reference', $orderId)->exists()) {
            Log::error("RCPAY PLUS Webhook: Shop sales not found for Transaction Reference {$orderId}.");
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        // F-1: Atomic idempotency guard — the UPDATE acts as both the lock
        // and the check in a single operation. Only the first thread to
        // reach this statement will affect rows; concurrent retries will
        // see affectedRows === 0 and short-circuit without dispatching
        // duplicate events or notifications.
        $affectedRows = Sale::where('transaction_reference', $orderId)
            ->whereNull('payment_confirmed_at')
            ->update(['payment_confirmed_at' => now()]);

        if ($affectedRows === 0) {
            Log::info("RCPAY PLUS Webhook: Shop transaction {$orderId} already confirmed. Skipping.");
            return response()->json(['message' => 'OK'], 200);
        }

        // Audit #13: resolve display data here so the queued notification
        // listener doesn't re-query the sales rows. Eager-load explicitly now
        // that Sale no longer auto-loads relations (audit #12).
        $sales = Sale::where('transaction_reference', $orderId)->with('customer.user')->get();
        $firstSale = $sales->first();
        $customerUser = $firstSale && $firstSale->customer ? $firstSale->customer->user : null;
        $context = [
            'customer_user_id' => $customerUser?->id,
            'customer_name' => $customerUser?->name ?? 'Customer',
            'display_amount' => (float) $sales->sum('total_price'),
            'part_name' => null,
        ];

        // Fire Domain Event to decouple Notification processing
        \App\Events\PaymentReceived::dispatch('shop', $orderId, $amount, $context);

        return response()->json(['message' => 'OK'], 200);
    }
}
