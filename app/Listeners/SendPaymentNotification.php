<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Models\PreOrder;
use App\Models\Sale;
use App\Models\User;
use App\Notifications\StatusUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendPaymentNotification implements ShouldQueue
{
    /**
     * Handle the event asynchronously.
     */
    public function handle(PaymentReceived $event): void
    {
        $owners = User::where('role', 'owner')->get();

        if ($event->type === 'preorder') {
            $parts = explode('-', $event->orderId);
            if (count($parts) < 2) return;

            $preorder = PreOrder::find($parts[1]);
            if (!$preorder) return;

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
        }

        if ($event->type === 'shop') {
            $sales = Sale::where('transaction_reference', $event->orderId)->get();
            if ($sales->isEmpty()) return;

            $firstSale = $sales->first();
            $customerUser = $firstSale->customer ? $firstSale->customer->user : null;
            $customerName = $customerUser ? $customerUser->name : 'Customer';
            $totalAmount = $sales->sum('total_price');

            Notification::send($owners, new StatusUpdated(
                'Shop Order Payment Confirmed (RCPAY)',
                "Payment of ₱" . number_format($totalAmount, 2) . " for Order #{$event->orderId} from {$customerName} has been successfully verified.",
                "/sales/{$event->orderId}/receipt"
            ));

            if ($customerUser) {
                $customerUser->notify(new StatusUpdated(
                    'Shop Order Payment Confirmed',
                    "Your payment of ₱" . number_format($totalAmount, 2) . " for Order #{$event->orderId} has been successfully verified! Thank you for your purchase.",
                    "/shop/receipt?trx={$event->orderId}"
                ));
            }
        }
    }
}
