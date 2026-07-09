<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Models\User;
use App\Notifications\StatusUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendPaymentNotification implements ShouldQueue
{
    /**
     * Handle the event asynchronously.
     *
     * Audit #13: reads pre-resolved display data from the event's context
     * (populated in the webhook controller) instead of re-querying the
     * PreOrder / Sale models. Only a single targeted User lookup remains,
     * which is required to deliver the customer's database notification.
     */
    public function handle(PaymentReceived $event): void
    {
        $owners = User::owners();
        $customerName = $event->context['customer_name'] ?? 'Customer';
        $displayAmount = (float) ($event->context['display_amount'] ?? $event->amount);
        $customerUserId = $event->context['customer_user_id'] ?? null;
        $customerUser = $customerUserId ? User::find($customerUserId) : null;

        if ($event->type === 'preorder') {
            $partName = $event->context['part_name'] ?? '';

            Notification::send($owners, new StatusUpdated(
                'Pre-order Payment Received (RCPAY)',
                "Customer {$customerName} has completed the down payment of ₱" . number_format($displayAmount, 2) . " for {$partName}.",
                "/preorders"
            ));

            if ($customerUser) {
                $customerUser->notify(new StatusUpdated(
                    'Pre-order Down Payment Confirmed',
                    "Your down payment of ₱" . number_format($displayAmount, 2) . " for {$partName} has been successfully verified! We'll start sourcing your part.",
                    "/customer/dashboard"
                ));
            }
        }

        if ($event->type === 'shop') {
            Notification::send($owners, new StatusUpdated(
                'Shop Order Payment Confirmed (RCPAY)',
                "Payment of ₱" . number_format($displayAmount, 2) . " for Order #{$event->orderId} from {$customerName} has been successfully verified.",
                "/sales/{$event->orderId}/receipt"
            ));

            if ($customerUser) {
                $customerUser->notify(new StatusUpdated(
                    'Shop Order Payment Confirmed',
                    "Your payment of ₱" . number_format($displayAmount, 2) . " for Order #{$event->orderId} has been successfully verified! Thank you for your purchase.",
                    "/checkout/receipt?trx={$event->orderId}"
                ));
            }
        }
    }
}
