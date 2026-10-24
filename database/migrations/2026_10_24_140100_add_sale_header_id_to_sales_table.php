<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SYS4-06 Stage D1: link sales (detail lines) to sale_headers (step 2 of 3).
 *
 * Adds ONE nullable column. Existing rows keep NULL (historical/legacy sales are valid
 * until a separate, explicit backfill attaches them); no existing column, id or value
 * changes. The FK is RESTRICT: a header that still has lines can never be deleted.
 *
 * PostgreSQL: ADD COLUMN ... NULL is a metadata-only change, but adding the FK takes a
 * brief lock on sales and sale_headers and validates existing rows (all NULL, so it is
 * quick at this table size). The supporting index is deliberately NOT created here; see
 * the next migration, which builds it CONCURRENTLY.
 *
 * ROLLBACK: drops the FK and column, but ONLY while every link is NULL. If any sales row is
 * already attached to a header, down() refuses before changing anything, because dropping the
 * column would silently destroy the header/detail relationships (the lines would survive but
 * could no longer be tied back to their transaction). Detach deliberately first, from a backup.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('sales', 'sale_header_id')) {
            return;
        }

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('sale_header_id')->nullable()->constrained('sale_headers')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('sales', 'sale_header_id')) {
            return;
        }

        // Safety check first: nothing below may run if it would discard populated links.
        $linked = DB::table('sales')->whereNotNull('sale_header_id')->count();
        if ($linked > 0) {
            throw new RuntimeException("Refusing to drop sales.sale_header_id: {$linked} sales row(s) are linked to a header and the links would be lost. Back up and detach them deliberately before rolling back.");
        }

        // SQLite cannot drop an indexed column; PostgreSQL would drop the index with it.
        if (Schema::hasIndex('sales', 'idx_sales_sale_header_id')) {
            Schema::table('sales', fn (Blueprint $t) => $t->dropIndex('idx_sales_sale_header_id'));
        }

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['sale_header_id']);
            $table->dropColumn('sale_header_id');
        });
    }
};
