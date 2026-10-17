<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Release A (S1): additive, nullable "sourced" milestone for pre-orders.
 *
 * - Purely additive: no existing column, status value or row is changed.
 * - Legacy rows keep sourced_at = NULL and continue to work: they display as
 *   "Pending Sourcing" until staff act, and "Mark arrived" does not require
 *   a prior "Mark sourced".
 *
 * ROLLBACK STRATEGY (production): revert the application code. The column is
 * nullable and unused by older code, so it can safely stay in place. Do NOT run
 * migrate:rollback on production; dropping the column would only discard the
 * sourcing timestamps and is never required to restore previous behaviour.
 * down() exists for local/test environments.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pre_orders', 'sourced_at')) {
            return;
        }

        Schema::table('pre_orders', function (Blueprint $table) {
            $table->timestamp('sourced_at')->nullable()->after('is_stocked');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('pre_orders', 'sourced_at')) {
            return;
        }

        Schema::table('pre_orders', function (Blueprint $table) {
            $table->dropColumn('sourced_at');
        });
    }
};
