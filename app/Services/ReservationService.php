<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    /**
     * Cancel an active reservation and restore its stock exactly once.
     */
    public function release(Sale $sale, string $reason, string $status = 'cancelled'): bool
    {
        return DB::transaction(function () use ($sale, $reason, $status) {
            $lockedSale = Sale::lockForUpdate()->find($sale->id);

            if (!$lockedSale || $lockedSale->status !== 'pending') {
                return false;
            }

            // Restore stock
            if ($lockedSale->inventory_item_id) {
                $inventory = InventoryItem::lockForUpdate()->find($lockedSale->inventory_item_id);
                if ($inventory) {
                    $inventory->increment('quantity', $lockedSale->quantity);
                }
            }

            $lockedSale->update([
                'status' => $status,
                'cancellation_reason' => $reason,
            ]);

            return true;
        });
    }
}
