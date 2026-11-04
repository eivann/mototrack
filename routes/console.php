<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::command('reservations:expire')->everyMinute()->withoutOverlapping();
\Illuminate\Support\Facades\Schedule::command('preorders:expire')->everyMinute()->withoutOverlapping();
// RD2-R3 Phase 5: both are inert while their flags are false (RR_AUTO_CANCEL_ENABLED / RR_PENDING_EXPIRY_ENABLED).
// Manual runs are dry-run by default; the scheduler passes --execute, which still honors each flag.
\Illuminate\Support\Facades\Schedule::command('repair-reservations:auto-cancel --execute')->everyMinute()->withoutOverlapping();
\Illuminate\Support\Facades\Schedule::command('repair-reservations:expire-pending --execute')->everyMinute()->withoutOverlapping();
