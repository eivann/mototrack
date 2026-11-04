<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RD2-R3 Phase 4: owner-managed repair-reservation blackouts. ADDITIVE and REVERSIBLE. LOCAL ONLY — applies to
 * no production/Supabase database. Earlier R3 migrations are not edited, and nothing is backfilled.
 *
 * A blackout is one explicit half-open interval [starts_at, ends_at) in the app timezone (the same storage
 * convention as repair_reservations.scheduled_at), whatever the owner entered:
 *   slot  one appointment slot            [slot start, slot start + slot_minutes)
 *   range a time range on one day         [from, to)
 *   day   a whole calendar day            [00:00, 00:00 next day)
 * `kind` is informational (how it was entered). Overlap is always decided on the interval, never on `kind`.
 * Plain portable columns only (SQLite for tests, PostgreSQL in production); no raw SQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_blackouts', function (Blueprint $table) {
            $table->id();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('kind', 10);
            $table->string('reason', 255)->nullable();   // owner-facing only, never shown to customers
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['starts_at', 'ends_at'], 'rb_window_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_blackouts');
    }
};
