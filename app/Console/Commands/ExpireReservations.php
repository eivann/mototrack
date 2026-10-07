<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('reservations:expire')]
#[Description('Cancel pending reservations older than 24 hours and restore stock')]
class ExpireReservations extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cutoff = now()->subHours(24);

        $expiredSales = \App\Models\Sale::where('status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->get();

        $count = 0;
        foreach ($expiredSales as $sale) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($sale) {
                // Lock the sale
                $sale = \App\Models\Sale::lockForUpdate()->find($sale->id);
                if ($sale->status === 'pending') {
                    $sale->update([
                        'status' => 'cancelled',
                        'cancellation_reason' => 'Automatically expired after 24 hours',
                    ]);

                    // Restore stock
                    $inventory = \App\Models\InventoryItem::lockForUpdate()->find($sale->inventory_item_id);
                    if ($inventory) {
                        $inventory->increment('quantity', $sale->quantity);
                    }
                }
            });
            $count++;
        }

        $this->info("Expired {$count} reservations.");
    }
}
