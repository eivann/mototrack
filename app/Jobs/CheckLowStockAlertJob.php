<?php

namespace App\Jobs;

use App\Models\InventoryItem;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckLowStockAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $inventoryItemId) {}

    /**
     * Execute the job.
     *
     * Checks if the inventory item has dropped below its low-stock
     * threshold and dispatches an SMS alert if it hasn't been sent yet.
     * Running this in the queue ensures the checkout/POS response
     * is never blocked by external SMS API latency.
     */
    public function handle(): void
    {
        $item = InventoryItem::find($this->inventoryItemId);

        if ($item) {
            SmsService::checkLowStockAlert($item);
        }
    }
}
