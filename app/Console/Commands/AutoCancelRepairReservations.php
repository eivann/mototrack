<?php

namespace App\Console\Commands;

use App\Services\RepairReservationService;
use Illuminate\Console\Command;

/**
 * RD2-R3 Phase 5: automatic NO-CHECK-IN cancellation (Asia/Manila, stored check-in deadline).
 *
 * SAFE BY DEFAULT:
 *  - A manual run is a read-only DRY-RUN (no writes, no notifications); real changes need --execute.
 *  - Neither mode does anything unless RR_AUTO_CANCEL_ENABLED=true. There is no option that bypasses the flag.
 */
class AutoCancelRepairReservations extends Command
{
    protected $signature = 'repair-reservations:auto-cancel
                            {--execute : Actually cancel eligible reservations (still requires RR_AUTO_CANCEL_ENABLED=true)}
                            {--dry-run : Explicit read-only preview (this is already the default)}';

    protected $description = 'Cancel accepted repair reservations with no check-in after their stored check-in deadline (disabled by default; dry-run unless --execute)';

    public function handle(RepairReservationService $service): int
    {
        $execute = (bool) $this->option('execute') && ! $this->option('dry-run');
        $r = $service->runAutoCancel(null, ! $execute);

        if (! $r['enabled']) {
            $this->info('Automatic no-check-in cancellation is disabled (RR_AUTO_CANCEL_ENABLED=false). Nothing changed.');

            return self::SUCCESS;
        }

        $this->info(($r['dry_run'] ? '[dry-run] ' : '')."eligible {$r['eligible']}, cancelled {$r['processed']}, skipped {$r['skipped']}, failed {$r['failed']}"
            .($r['dry_run'] && $r['would'] ? '; would cancel: '.implode(', ', $r['would']) : '').'.');

        return $r['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
