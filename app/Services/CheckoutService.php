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
        // Normalize the associative cart (keyed by item id) into an ordered list.
        $lines = [];
        foreach ($cart as $id => $item) {
            $lines[] = ['id' => $id, 'quantity' => $item['quantity']];
        }

        return $this->commitOrder->execute(
            $lines,
            $transactionReference,
            $customerId,
            'Stock issue detected for: ',
            fn (bool $isFirst, float $grandTotal) => $isFirst ? ($amountTenderedInput ?: $grandTotal) : 0,
            fn (bool $isFirst, float $grandTotal) => $isFirst ? max(0, ($amountTenderedInput ?? 0) - $grandTotal) : 0,
            dispatchLowStockAlert: false
        );
    }
}
