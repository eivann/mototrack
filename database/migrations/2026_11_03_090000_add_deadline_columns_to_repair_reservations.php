<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RD2-R3 Phase 1 (schedule & deadlines): persist the check-in and approval deadlines on
 * repair_reservations so that pending expiry and the strict acceptance cutoff have a fixed
 * boundary, stored once when the customer creates the request (not re-derived at decision time).
 *
 *  - ADDITIVE and REVERSIBLE. LOCAL ONLY — this change applies to no production/Supabase database.
 *  - The original 2026_10_31_120000 migration is NOT edited.
 *  - Both columns are nullable. Existing PENDING and ACCEPTED rows are backfilled from their
 *    scheduled_at using the configured check-in minutes (30) and approval buffer (0 by default).
 *    No rows are deleted and no status is changed. On a fresh test database the tables are empty,
 *    so the backfill is a harmless no-op.
 *  - All times are Asia/Manila wall time (app.timezone), matching how scheduled_at is stored; the
 *    backfill does only wall-clock subtraction and never shifts the timezone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_reservations', function (Blueprint $table) {
            $table->timestamp('check_in_deadline_at')->nullable()->after('scheduled_at');
            $table->timestamp('approval_deadline_at')->nullable()->after('check_in_deadline_at');

            // Scheduler scans: unchecked accepted rows past the check-in deadline (auto-cancel, later phase)
            // and unanswered pending rows past the approval deadline (pending expiry, later phase).
            $table->index(['status', 'check_in_deadline_at'], 'rr_deadline_idx');
            $table->index(['status', 'approval_deadline_at'], 'rr_approval_idx');
        });

        $checkIn = max(0, (int) config('repair_reservations.check_in.minutes_before', 30));
        $buffer = max(0, (int) config('repair_reservations.approval.buffer_minutes', 0));

        DB::table('repair_reservations')
            ->whereIn('status', ['pending', 'accepted'])
            ->whereNull('check_in_deadline_at')
            ->orderBy('id')
            ->get(['id', 'scheduled_at'])
            ->each(function ($row) use ($checkIn, $buffer) {
                $scheduled = Carbon::parse($row->scheduled_at);
                $checkInAt = $scheduled->copy()->subMinutes($checkIn);
                $approvalAt = $checkInAt->copy()->subMinutes($buffer);

                DB::table('repair_reservations')->where('id', $row->id)->update([
                    'check_in_deadline_at' => $checkInAt,
                    'approval_deadline_at' => $approvalAt,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('repair_reservations', function (Blueprint $table) {
            $table->dropIndex('rr_deadline_idx');
            $table->dropIndex('rr_approval_idx');
            $table->dropColumn(['check_in_deadline_at', 'approval_deadline_at']);
        });
    }
};
