<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RD2-R3: customer repair / service appointment requests.
 *
 * LOCAL MIGRATION ONLY — not applied to any live/production database by this change.
 *
 * Deliberately separate from `repairs` (owner-created job tickets): a request is not a job until the
 * owner accepts it, and the existing repair module is left untouched.
 *
 *  - `active_day_key` is NULL once a request is rejected/cancelled; its UNIQUE index gives portable
 *    (SQLite + PostgreSQL) protection against one customer holding two active requests for one day
 *    (double submit / double click).
 *  - `repair_reservation_days` is a lock row per service date, locked while the owner accepts, so two
 *    simultaneous acceptances cannot both pass the capacity check.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->date('service_date');                     // Asia/Manila calendar day
            $table->timestamp('scheduled_at');                // requested start (absolute instant)
            $table->string('service_type', 40);
            $table->string('bike_model', 80);
            $table->text('description');
            $table->string('status', 20)->default('pending'); // pending | accepted | rejected | cancelled
            $table->string('active_day_key', 40)->nullable()->unique();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_reason', 255)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_by', 12)->nullable();   // customer | owner | system
            $table->timestamps();

            $table->index(['service_date', 'status']);
            $table->index(['status', 'scheduled_at']);
            $table->index('customer_id');
        });

        Schema::create('repair_reservation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_reservation_id')->constrained('repair_reservations')->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->string('actor_role', 12);                 // customer | owner | system
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('repair_reservation_id');
        });

        Schema::create('repair_reservation_days', function (Blueprint $table) {
            $table->date('service_date')->primary();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_reservation_days');
        Schema::dropIfExists('repair_reservation_events');
        Schema::dropIfExists('repair_reservations');
    }
};
