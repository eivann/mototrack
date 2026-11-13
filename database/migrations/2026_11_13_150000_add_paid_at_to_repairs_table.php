<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 F08 (Repair Transaction Dates): record the exact timestamp when a repair
 * is paid. Nullable; existing historical paid repairs remain null without inventing
 * historical timestamps.
 *
 * ADDITIVE and REVERSIBLE. LOCAL ONLY — do not apply against production Supabase.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('is_paid');
        });
    }

    public function down(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            $table->dropColumn('paid_at');
        });
    }
};

