<?php

namespace App\Jobs;

use App\Models\InventoryItem;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Optional, best-effort SMS companion to the (synchronous) in-app low-stock
 * notification. Needs a queue worker; without one the SMS simply is not sent
 * and nothing else is affected. It never touches the alert state flag.
 */
class SendLowStockSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $inventoryItemId) {}

    public function handle(): void
    {
        $item = InventoryItem::find($this->inventoryItemId);

        if ($item && (int) $item->quantity <= (int) ($item->low_stock_threshold ?? 5)) {
            SmsService::sendLowStock($item);
        }
    }
}
