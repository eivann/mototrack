<?php

namespace App\Jobs;

use App\Services\LowStockAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * LEGACY. Low-stock alerts are now evaluated synchronously by
 * LowStockAlertService at every quantity-changing path and no code dispatches
 * this job any more. The class is kept so that any CheckLowStockAlertJob rows
 * already sitting in a production `jobs` table can still be processed safely:
 * it delegates to the same idempotent state machine, so a stale job can never
 * create a duplicate in-app alert (the flag is already set) and never needs
 * to be purged.
 */
class CheckLowStockAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $inventoryItemId) {}

    public function handle(): void
    {
        app(LowStockAlertService::class)->evaluate($this->inventoryItemId);
    }
}
