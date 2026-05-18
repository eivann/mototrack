<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    /**
     * Process the cart and generate sales records.
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
        return DB::transaction(function () use ($cart, $transactionReference, $customerId, $amountTenderedInput) {
            // Pass 1: Lock stock and precisely calculate the true grand total
            $finalGrandTotal = 0;
            $lockedItems = [];
            
            foreach ($cart as $id => $item) {
                $inventory = InventoryItem::lockForUpdate()->find($id);

                if (!$inventory || $inventory->quantity < $item['quantity']) {
                    throw new \Exception('Stock issue detected for: ' . ($inventory ? $inventory->part_name : 'Unknown item'));
                }

                $finalGrandTotal += ($inventory->price * $item['quantity']);
                $lockedItems[$id] = $inventory;
            }

            $isFirstItem = true;

            // Pass 2: Generate sales records using the accurate grand total
            foreach ($cart as $id => $item) {
                $inventory = $lockedItems[$id];
                $total = $inventory->price * $item['quantity'];

                Sale::create([
                    'inventory_item_id' => $id,
                    'customer_id' => $customerId,
                    'quantity' => $item['quantity'],
                    'price' => $inventory->price,
                    'total_price' => $total,
                    'transaction_reference' => $transactionReference,
                    'amount_tendered' => $isFirstItem ? ($amountTenderedInput ?: $finalGrandTotal) : 0,
                    'change' => $isFirstItem ? (max(0, ($amountTenderedInput ?? 0) - $finalGrandTotal)) : 0,
                ]);

                $inventory->decrement('quantity', $item['quantity']);
                $isFirstItem = false;
            }

            return $finalGrandTotal;
        });
    }
}
