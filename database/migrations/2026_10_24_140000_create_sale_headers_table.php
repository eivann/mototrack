<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SYS4-06 Stage D1: transaction header table (physical header/detail, step 1 of 3).
 *
 * `sale_headers` holds one row per transaction; the existing `sales` table stays the
 * line/detail table (linked by sales.sale_header_id, added in the next migration).
 *
 * Purely additive: a NEW table, no existing table is touched, nothing is backfilled.
 * Safe to run while the current application code is live (nothing references it yet).
 *
 * Money columns are decimal(12,2), one step wider than sales.total_price /
 * amount_tendered / change (decimal(10,2)): a header total is the SUM of many lines, and
 * the tender must be able to cover that sum, so a 10,2 column could overflow where every
 * single line fits. total_amount is the ORIGINAL transaction total (never reduced by
 * refunds; refunds stay on product_returns / line status).
 *
 * ROLLBACK: down() refuses to drop the table while sales.sale_header_id still exists or
 * while the table holds rows, so a populated header table is never destroyed by accident.
 * Roll back newest-first (index, column, then this table).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sale_headers')) {
            return;
        }

        Schema::create('sale_headers', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_reference')->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->index('customer_id'); // PostgreSQL does not index FK columns; used by ON DELETE SET NULL and lookups
            $table->unsignedBigInteger('shop_id')->nullable()->index(); // same shape as sales.shop_id (no FK)
            $table->string('transaction_type', 32); // pos | online_reservation | repair | preorder | legacy
            $table->decimal('total_amount', 12, 2); // original transaction total
            $table->decimal('amount_tendered', 12, 2)->nullable();
            $table->decimal('change_amount', 12, 2)->nullable();
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('payment_confirmed_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sale_headers')) {
            return;
        }

        if (Schema::hasColumn('sales', 'sale_header_id')) {
            throw new RuntimeException('Refusing to drop sale_headers: sales.sale_header_id still references it. Roll back the sales.sale_header_id migrations first.');
        }

        if (DB::table('sale_headers')->exists()) {
            throw new RuntimeException('Refusing to drop sale_headers: it contains rows. Back up and clear it deliberately before rolling back.');
        }

        Schema::dropIfExists('sale_headers');
    }
};
