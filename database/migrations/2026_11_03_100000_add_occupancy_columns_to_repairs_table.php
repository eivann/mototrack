<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RD2-R3 Phase 2 (shop-wide simultaneous capacity): record WHEN a walk-in repair started occupying a
 * bay and how long it is expected to take, so its interval can be compared with accepted online
 * reservations.
 *
 *  - ADDITIVE and REVERSIBLE. LOCAL ONLY — this change applies to no production/Supabase database.
 *  - All three columns are nullable and nothing is backfilled: existing rows keep NULL.
 *    A legacy `ongoing` repair with NULL started_at is handled conservatively in
 *    App\Services\RepairCapacityService (it counts as occupying a bay until the owner marks it done).
 *  - Existing statuses, amounts, payments and receipts are not touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable();
            $table->unsignedSmallInteger('estimated_minutes')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->index(['status', 'started_at'], 'repairs_occupancy_idx');
        });
    }

    public function down(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            $table->dropIndex('repairs_occupancy_idx');
            $table->dropColumn(['started_at', 'estimated_minutes', 'completed_at']);
        });
    }
};
