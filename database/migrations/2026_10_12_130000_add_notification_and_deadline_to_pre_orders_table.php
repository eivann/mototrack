<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pre_orders', function (Blueprint $table) {
            $table->timestamp('ready_notified_at')->nullable()->after('is_stocked');
            $table->timestamp('pickup_expires_at')->nullable()->after('ready_notified_at');
            $table->index(['status', 'is_stocked', 'pickup_expires_at'], 'idx_preorders_pickup_expiry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pre_orders', function (Blueprint $table) {
            $table->dropIndex('idx_preorders_pickup_expiry');
            $table->dropColumn(['ready_notified_at', 'pickup_expires_at']);
        });
    }
};
