<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Models\PreOrder;
use App\Models\Sale;
use Illuminate\Support\Facades\Log;

class UpdateOrderStatus
{
    /**
     * Handle the event.
     */
    public function handle(PaymentReceived $event): void
    {
        if ($event->type === 'preorder') {
            $parts = explode('-', $event->orderId);
            if (count($parts) < 2) return;

            $preorderId = $parts[1];
            $preorder = PreOrder::find($preorderId);

            if ($preorder && !$preorder->is_paid) {
                $preorder->is_paid = true;
                $preorder->save();
                Log::info("Event Listener: Pre-order {$preorderId} successfully marked as PAID.");
            }
        }

        if ($event->type === 'shop') {
            $sales = Sale::where('transaction_reference', $event->orderId)->get();
            if ($sales->isNotEmpty()) {
                Log::info("Event Listener: Shop transaction {$event->orderId} verified in database.");
            }
        }
    }
}
