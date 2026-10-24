<?php

namespace App\Actions;

use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\SaleHeader;
use Illuminate\Support\Facades\DB;

/**
 * Audit #6: the shared order-commit domain action extracted from
 * CheckoutService and SaleController::storeSale. Both callers previously
 * duplicated the two-pass "lock stock → compute grand total → create sales →
 * decrement" logic.
 *
 * The two call sites differ in what tendered amount / change they record and in
 * whether a low-stock alert is dispatched. Today both put the tender and change on
 * the FIRST line only (later lines get 0): POS records the cash received and the
 * unclamped change (change can be negative), the online cart records 0 / 0 because
 * nothing is collected at reservation time. Those differences are injected as
 * closures/flags so the behavior of each path is preserved exactly.
 *
 * SYS4-06 D2A: every committed transaction also gets exactly one `sale_headers` row and
 * every line created here carries its `sale_header_id`. The header, the lines and the stock
 * decrements share the one DB transaction, so a failure anywhere leaves none of them behind.
 * A transaction reference that is already used (by a header OR by existing sales lines) is
 * refused outright; a new transaction is never attached to someone else's header.
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

            // Pass 2: create the header, then the sales records against the accurate grand total.
            $now = now();

            // One reference = one transaction. Refuse (and roll back) rather than ever share a
            // header; the unique index on sale_headers.transaction_reference backstops a race.
            if (
                SaleHeader::where('transaction_reference', $transactionReference)->exists()
                || Sale::withTrashed()->where('transaction_reference', $transactionReference)->exists()
            ) {
                throw new \RuntimeException("Transaction reference {$transactionReference} is already in use.");
            }

            $isReservation = $status === 'pending';

            // First-line tender/change are evaluated exactly once (tender, then change, as before)
            // and the SAME values go to the header and to the first line, so they cannot diverge
            // even if a callback is stateful. A shortfall still throws here, before any write.
            $firstTender = $tenderFor(true, $grandTotal);
            $firstChange = $changeFor(true, $grandTotal);

            $header = SaleHeader::create([
                'transaction_reference' => $transactionReference,
                'customer_id' => $customerId,
                'transaction_type' => $isReservation ? SaleHeader::TYPE_ONLINE_RESERVATION : SaleHeader::TYPE_POS,
                'total_amount' => round($grandTotal, 2),
                'amount_tendered' => $firstTender,
                'change_amount' => $firstChange,
                'reserved_at' => $isReservation ? $now : null,
                'expires_at' => $isReservation ? $now->copy()->addHours(24) : null,
                'payment_confirmed_at' => $status === 'completed' ? $now : null,
            ]);

            $isFirst = true;
            foreach ($locked as $entry) {
                $inventory = $entry['inventory'];
                $quantity = $entry['quantity'];

                // Later lines keep the original per-line callback calls (tender, then change).
                $lineTender = $isFirst ? $firstTender : $tenderFor(false, $grandTotal);
                $lineChange = $isFirst ? $firstChange : $changeFor(false, $grandTotal);

                Sale::create([
                    'sale_header_id' => $header->id,
                    'inventory_item_id' => $inventory->id,
                    'customer_id' => $customerId,
                    'quantity' => $quantity,
                    'price' => $inventory->price,
                    'total_price' => $inventory->price * $quantity,
                    'transaction_reference' => $transactionReference,
                    'amount_tendered' => $lineTender,
                    'change' => $lineChange,
                    'status' => $status,
                    'reserved_at' => $isReservation ? $now : null,
                    'expires_at' => $isReservation ? $now->copy()->addHours(24) : null,
                    'payment_confirmed_at' => $status === 'completed' ? $now : null,
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
