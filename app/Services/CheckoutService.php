<?php

namespace App\Services;

use App\Actions\CommitOrder;

class CheckoutService
{
    public function __construct(protected CommitOrder $commitOrder)
    {
    }

    /**
     * Process the cart and generate sales records.
     *
     * Audit #6: delegates the two-pass lock/total/create/decrement logic to the
     * shared CommitOrder action. Tender/change semantics for the online cart:
     * only the first row carries the tendered amount (defaulting to the grand
     * total when none was provided) and the change is clamped at zero.
     *
     * @param array $cart
     * @param string $transactionReference
     * @param int|null $customerId
     * @param float $amountTenderedInput
     * @return float The grand total of the transaction
     * @throws \Exception
     */
    public function processTransaction(array $cart, $transactionReference, $customerId, $amountTenderedInput)
    {
        // Normalize and aggregate duplicate SKUs in the cart.
        $lines = [];
        $aggregated = [];
        foreach ($cart as $id => $item) {
            if (!isset($aggregated[$id])) {
                $aggregated[$id] = 0;
            }
            // Ensure positive integers with bounded maximum
            $qty = max(1, min((int) $item['quantity'], 9999));
            $aggregated[$id] += $qty;
        }
        foreach ($aggregated as $id => $qty) {
            $lines[] = ['id' => (int) $id, 'quantity' => $qty];
        }

        return $this->commitOrder->execute(
            $lines,
            $transactionReference,
            $customerId,
            'Stock issue detected for: ',
            fn (bool $isFirst, float $grandTotal) => 0, // No payment online
            fn (bool $isFirst, float $grandTotal) => 0, // No change online
            dispatchLowStockAlert: false,
            status: 'pending'
        );
    }
}
