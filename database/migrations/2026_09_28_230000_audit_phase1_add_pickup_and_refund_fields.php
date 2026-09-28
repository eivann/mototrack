<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit Phase 1 — Foundation migration
 *
 * Adds:
 * - sales.picked_up_at            → Track whether customer has physically picked up the item
 * - refund_requests.refund_method → 'digital' or 'on_site'
 * - refund_requests.qr_code_path  → QR code image for digital refunds
 * - refund_requests.pickup_time   → Scheduled time for on-site refund pickups
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1.1 — Add pickup tracking to sales
        Schema::table('sales', function (Blueprint $table) {
            $table->timestamp('picked_up_at')->nullable()->after('cancellation_reason');
        });

        // 1.2 — Add refund method fields to refund_requests
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->string('refund_method')->nullable()->after('status'); // 'digital' or 'on_site'
            $table->string('qr_code_path')->nullable()->after('refund_method'); // For digital refunds
            $table->datetime('pickup_time')->nullable()->after('qr_code_path'); // For on-site refunds
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('picked_up_at');
        });

        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropColumn(['refund_method', 'qr_code_path', 'pickup_time']);
        });
    }
};
