<?php

namespace App\Console\Commands;

use App\Services\RepairReservationService;
use Illuminate\Console\Command;

/**
 * RD2-R3 Phase 5: expire unanswered PENDING repair requests at their stored approval deadline (Asia/Manila).
 *
 * SAFE BY DEFAULT:
 *  - A manual run is a read-only DRY-RUN (no writes, no notifications); real changes need --execute.
 *  - Neither mode does anything unless RR_PENDING_EXPIRY_ENABLED=true. There is no option that bypasses the flag.
 */
class ExpirePendingRepairReservations extends Command
{
    protected $signature = 'repair-reservations:expire-pending
                            {--execute : Actually expire eligible requests (still requires RR_PENDING_EXPIRY_ENABLED=true)}
                            {--dry-run : Explicit read-only preview (this is already the default)}';

    protected $description = 'Expire pending repair requests whose stored approval deadline has been reached (disabled by default; dry-run unless --execute)';

    public function handle(RepairReservationService $service): int
    {
        $execute = (bool) $this->option('execute') && ! $this->option('dry-run');
        $r = $service->runPendingExpiry(null, ! $execute);

        if (! $r['enabled']) {
            $this->info('Pending-request expiry is disabled (RR_PENDING_EXPIRY_ENABLED=false). Nothing changed.');

            return self::SUCCESS;
        }

        $this->info(($r['dry_run'] ? '[dry-run] ' : '')."eligible {$r['eligible']}, expired {$r['processed']}, skipped {$r['skipped']}, failed {$r['failed']}"
            .($r['dry_run'] && $r['would'] ? '; would expire: '.implode(', ', $r['would']) : '').'.');

        return $r['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
