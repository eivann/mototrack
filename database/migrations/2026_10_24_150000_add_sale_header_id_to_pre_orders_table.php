<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SYS4-06 Stage D2D: link a paid pre-order to the sale_headers row created when it was paid.
 *
 * Adds ONE nullable column. Every existing pre-order keeps NULL (they stay valid, and are never
 * matched to a header by customer, item, reference pattern or time). The FK is RESTRICT, and the
 * UNIQUE index guarantees one header can belong to at most one pre-order (PostgreSQL and SQLite
 * both allow many NULLs in a unique index).
 *
 * PostgreSQL: ADD COLUMN ... NULL is metadata-only; the FK and unique index take a brief lock on
 * pre_orders (a small table whose rows are all NULL, so validation and the build are instant).
 *
 * ROLLBACK: refuses, before changing anything, while any pre-order is linked, because dropping
 * the column would silently destroy the pre-order -> header relationship.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pre_orders', 'sale_header_id')) {
            return;
        }

        Schema::table('pre_orders', function (Blueprint $table) {
            $table->foreignId('sale_header_id')->nullable()->constrained('sale_headers')->restrictOnDelete();
            $table->unique('sale_header_id', 'uq_pre_orders_sale_header_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('pre_orders', 'sale_header_id')) {
            return;
        }

        $linked = DB::table('pre_orders')->whereNotNull('sale_header_id')->count();
        if ($linked > 0) {
            throw new RuntimeException("Refusing to drop pre_orders.sale_header_id: {$linked} pre-order(s) are linked to a sale header and the links would be lost. Back up and detach them deliberately before rolling back.");
        }

        Schema::table('pre_orders', function (Blueprint $table) {
            $table->dropUnique('uq_pre_orders_sale_header_id');
            $table->dropForeign(['sale_header_id']);
            $table->dropColumn('sale_header_id');
        });
    }
};
