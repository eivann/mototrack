<?php

namespace App\Actions;

use App\Models\Sale;
use App\Models\SaleHeader;
use Illuminate\Support\Str;

/**
 * SYS4-06 D2B: records a single-line cash sale (repair payment, pre-order payment/pickup) together
 * with its physical SaleHeader. It performs NO transaction handling of its own: the caller must
 * already be inside its DB::transaction so the header, the sale and the caller's own state changes
 * commit or roll back together.
 */
class RecordHeaderedSale
{
    private const MAX_REFERENCE_ATTEMPTS = 5;

    /**
     * @param  array<string,mixed>  $line  Sale attributes (inventory_item_id, quantity, price, total_price,
     *                                     customer_id, ...). Tender/change/status/reference/timestamps are set here.
     * @param  string  $type  A SaleHeader::TYPE_* value.
     * @param  string  $prefix  Existing reference prefix, e.g. 'REPAIR-' or 'PRE-'.
     */
    public function execute(string $type, string $prefix, array $line, float $tendered, bool $pickedUp = false): Sale
    {
        $total = (float) $line['total_price'];
        $change = round($tendered - $total, 2);
        $now = now();
        $reference = $this->freshReference($prefix);

        $header = SaleHeader::create([
            'transaction_reference' => $reference,
            'customer_id' => $line['customer_id'] ?? null,
            'transaction_type' => $type,
            'total_amount' => round($total, 2),
            'amount_tendered' => $tendered,
            'change_amount' => $change,
            'payment_confirmed_at' => $now,
            'picked_up_at' => $pickedUp ? $now : null,
        ]);

        // array_merge: the action's authoritative fields come LAST so they always win over any
        // conflicting key the caller put in $line (the `+` union operator would let $line win).
        return Sale::create(array_merge($line, [
            'sale_header_id' => $header->id,
            'amount_tendered' => $tendered,
            'change' => $change,
            'status' => 'completed',
            'payment_confirmed_at' => $now,
            'picked_up_at' => $pickedUp ? $now : null,
            'transaction_reference' => $reference,
        ]));
    }

    /**
     * Bounded regeneration, checked BEFORE any write (a SELECT cannot poison a PostgreSQL transaction).
     * The unique index on sale_headers.transaction_reference stays the final safeguard.
     */
    private function freshReference(string $prefix): string
    {
        for ($i = 0; $i < self::MAX_REFERENCE_ATTEMPTS; $i++) {
            $reference = $prefix.strtoupper(Str::random(8));

            if (! SaleHeader::where('transaction_reference', $reference)->exists()
                && ! Sale::withTrashed()->where('transaction_reference', $reference)->exists()) {
                return $reference;
            }
        }

        throw new \RuntimeException("Could not generate a unique {$prefix} transaction reference.");
    }
}
