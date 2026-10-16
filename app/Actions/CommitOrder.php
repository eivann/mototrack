<?php

namespace App\Actions;

use App\Models\InventoryItem;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

/**
 * Audit #6: the shared order-commit domain action extracted from
 * CheckoutService and SaleController::storeSale. Both callers previously
 * duplicated the two-pass "lock stock → compute grand total → create sales →
 * decrement" logic.
 *
 * The two call sites differ in how the tendered amount / change are recorded
 * (POS stamps every row and allows negative change; the online cart stamps
 * only the first row and clamps change at zero) and in whether a low-stock
 * alert is dispatched. Those differences are injected as closures/flags so the
 * behavior of each path is preserved exactly.
 */
class CommitOrder
{
    /**
     * @param  array          $lines                 List of ['id' => int, 'quantity' => int], order preserved.
     * @param  string         $transactionReference
     * @param  int|null       $customerId
     * @param  string         $stockErrorPrefix      Prepended to the out-of-stock exception message.
     * @param  callable       $tenderFor             fn(bool $isFirst, float $grandTotal): float
     * @param  callable       $changeFor             fn(bool $isFirst, float $grandTotal): float
     * @param  bool           $dispatchLowStockAlert Deprecated no-op: every decrement now triggers the synchronous low-stock check.
     * @return float                                 The grand total.
     */
    public function execute(
        array $lines,
        string $transactionReference,
        ?int $customerId,
        string $stockErrorPrefix,
        callable $tenderFor,
        callable $changeFor,
        bool $dispatchLowStockAlert = false,
        string $status = 'completed'
    ): float {
        $totals = [];
        foreach ($lines as $line) {
            $id = filter_var($line['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $quantity = filter_var($line['quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

            if ($id === false || $quantity === false) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'cart' => 'Invalid inventory item or quantity.',
                ]);
            }

            $totals[$id] = ($totals[$id] ?? 0) + $quantity;
            if ($totals[$id] > 9999) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'cart' => 'Quantity exceeds the allowed limit.',
                ]);
            }
        }
        if (!$totals) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'cart' => 'Cart is empty.',
            ]);
        }
        ksort($totals, SORT_NUMERIC);
        $normalizedLines = [];
        foreach ($totals as $id => $quantity) {
            $normalizedLines[] = ['id' => $id, 'quantity' => $quantity];
        }

        return DB::transaction(function () use (
            $normalizedLines, $transactionReference, $customerId,
            $stockErrorPrefix, $tenderFor, $changeFor, $dispatchLowStockAlert, $status
        ) {
            // Pass 1: lock stock and compute the true grand total.
            $grandTotal = 0;
            $locked = [];

            foreach ($normalizedLines as $line) {
                $id = $line['id'];
                $quantity = $line['quantity'];
                $inventory = InventoryItem::lockForUpdate()->find($id);

                if (!$inventory || $inventory->quantity < $quantity) {
                    throw new \Exception($stockErrorPrefix . ($inventory ? $inventory->part_name : 'Unknown item'));
                }

                $grandTotal += ($inventory->price * $quantity);
                $locked[] = ['inventory' => $inventory, 'quantity' => $quantity];
            }

            // Pass 2: create sales records against the accurate grand total.
            $isFirst = true;
            foreach ($locked as $entry) {
                $inventory = $entry['inventory'];
                $quantity = $entry['quantity'];

                Sale::create([
                    'inventory_item_id' => $inventory->id,
                    'customer_id' => $customerId,
                    'quantity' => $quantity,
                    'price' => $inventory->price,
                    'total_price' => $inventory->price * $quantity,
                    'transaction_reference' => $transactionReference,
                    'amount_tendered' => $tenderFor($isFirst, $grandTotal),
                    'change' => $changeFor($isFirst, $grandTotal),
                    'status' => $status,
                    'reserved_at' => $status === 'pending' ? now() : null,
                    'expires_at' => $status === 'pending' ? now()->addHours(24) : null,
                    'payment_confirmed_at' => $status === 'completed' ? now() : null,
                ]);

                $inventory->decrement('quantity', $quantity);

                // Synchronous, queue-independent low-stock check (runs after commit).
                \App\Services\LowStockAlertService::checkAfterCommit($inventory->id);

                $isFirst = false;
            }

            return $grandTotal;
        });
    }
}
