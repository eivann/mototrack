<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\PreOrder;
use App\Models\Sale;
use App\Models\User;
use App\Notifications\StatusUpdated;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class RcpayWebhookController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Handle the RCPAY PLUS webhook.
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::info('RCPAY PLUS Webhook received:', $payload);

        // Verify the signature to prevent spoofing
        if (!$this->paymentService->verifyRcpayWebhook($payload)) {
            Log::warning('RCPAY PLUS Webhook: Invalid signature received.');
            return response()->json(['message' => 'Invalid Signature'], 400);
        }

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

        // Update pre-order payment status
        $preorder->is_paid = true;
        $preorder->save();

        Log::info("RCPAY PLUS Webhook: Pre-order {$preorderId} successfully marked as PAID.");

        // Notifications
        $owners = User::where('role', 'owner')->get();
        $customerUser = $preorder->customer ? $preorder->customer->user : null;
        $customerName = $customerUser ? $customerUser->name : $preorder->customer_name;

        Notification::send($owners, new StatusUpdated(
            'Pre-order Payment Received (RCPAY)',
            "Customer {$customerName} has completed the down payment of ₱" . number_format($preorder->down_payment, 2) . " for {$preorder->part_name}.",
            "/preorders"
        ));

        if ($customerUser) {
            $customerUser->notify(new StatusUpdated(
                'Pre-order Down Payment Confirmed',
                "Your down payment of ₱" . number_format($preorder->down_payment, 2) . " for {$preorder->part_name} has been successfully verified! We'll start sourcing your part.",
                "/customer/dashboard"
            ));
        }

        return response()->json(['message' => 'OK'], 200);
    }

    /**
     * Process Shop Order Payment.
     */
    protected function handleShopOrderPayment($orderId, $amount)
    {
        $sales = Sale::where('transaction_reference', $orderId)->get();

        if ($sales->isEmpty()) {
            Log::error("RCPAY PLUS Webhook: Shop sales not found for Transaction Reference {$orderId}.");
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        Log::info("RCPAY PLUS Webhook: Shop transaction {$orderId} successfully completed.");

        // Notifications
        $owners = User::where('role', 'owner')->get();
        $firstSale = $sales->first();
        $customerUser = $firstSale->customer ? $firstSale->customer->user : null;
        $customerName = $customerUser ? $customerUser->name : 'Customer';
        
        $totalAmount = $sales->sum('total_price');

        Notification::send($owners, new StatusUpdated(
            'Shop Order Payment Confirmed (RCPAY)',
            "Payment of ₱" . number_format($totalAmount, 2) . " for Order #{$orderId} from {$customerName} has been successfully verified.",
            "/sales/{$orderId}/receipt"
        ));

        if ($customerUser) {
            $customerUser->notify(new StatusUpdated(
                'Shop Order Payment Confirmed',
                "Your payment of ₱" . number_format($totalAmount, 2) . " for Order #{$orderId} has been successfully verified! Thank you for your purchase.",
                "/shop/receipt?trx={$orderId}"
            ));
        }

        return response()->json(['message' => 'OK'], 200);
    }
}
