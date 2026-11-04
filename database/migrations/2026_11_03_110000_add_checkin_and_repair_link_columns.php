<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RD2-R3 Phase 3 (owner check-in, reservation -> repair, completion). ADDITIVE and REVERSIBLE. LOCAL ONLY —
 * applies to no production/Supabase database. Phase 1 (…090000) and Phase 2 (…100000) are NOT edited.
 *
 * repair_reservations: every new column is nullable; existing rows, statuses and the Phase 1 deadline
 * columns are untouched (no backfill).
 *   checked_in_at / checked_in_by / received_by  owner-recorded physical check-in
 *   estimated_minutes / expected_end_at           duration chosen when the repair starts or is extended
 *   ended_at                                      actual completion (mirrors repairs.completed_at)
 *
 * repairs: ONE new column, reservation_id (nullable, UNIQUE, nullOnDelete): a reservation owns at most one
 * repair. repairs.completed_at (Phase 2) stays the only actual-completion timestamp, and the expected end of
 * a repair is derived (started_at + estimated_minutes), so neither a repair ended_at nor a repair
 * expected_end_at is added.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_reservations', function (Blueprint $table) {
            $table->timestamp('checked_in_at')->nullable();
            $table->unsignedBigInteger('checked_in_by')->nullable();
            $table->string('received_by', 80)->nullable();
            $table->unsignedSmallInteger('estimated_minutes')->nullable();
            $table->timestamp('expected_end_at')->nullable();
            $table->timestamp('ended_at')->nullable();
        });

        Schema::table('repairs', function (Blueprint $table) {
            $table->foreignId('reservation_id')->nullable()->unique()
                ->constrained('repair_reservations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reservation_id');
        });

        Schema::table('repair_reservations', function (Blueprint $table) {
            $table->dropColumn(['checked_in_at', 'checked_in_by', 'received_by', 'estimated_minutes', 'expected_end_at', 'ended_at']);
        });
    }
};
