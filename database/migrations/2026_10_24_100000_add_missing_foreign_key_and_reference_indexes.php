<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SYS4-06 Stage A: eight additive, NON-unique indexes that PostgreSQL does not create
 * automatically for foreign keys / the transaction_reference lookup key.
 *
 * Nothing is dropped, renamed or rewritten; no data is touched.
 *
 * PostgreSQL: CREATE INDEX CONCURRENTLY IF NOT EXISTS (does not block writes). It cannot run
 * inside a transaction block, so this migration opts out of Laravel's migration transaction.
 * Any INVALID leftover from an interrupted earlier concurrent build is dropped first, because
 * IF NOT EXISTS would otherwise silently keep the unusable index.
 *
 * Other drivers (SQLite for tests): ordinary Schema index creation, skipped when present.
 *
 * ROLLBACK: down() drops ONLY these eight indexes (DROP INDEX CONCURRENTLY IF EXISTS).
 * Prefer `php artisan migrate:rollback --path=database/migrations/2026_10_24_100000_add_missing_foreign_key_and_reference_indexes.php`
 * (or `--step=1` when this is the latest batch) so other migrations in the batch are not rolled back.
 */
return new class extends Migration
{
    /** PostgreSQL forbids CREATE/DROP INDEX CONCURRENTLY inside a transaction block. */
    public $withinTransaction = false;

    /** index name => [table, column] */
    public const INDEXES = [
        'idx_sales_transaction_reference'          => ['sales', 'transaction_reference'],
        'idx_product_returns_sale_id'              => ['product_returns', 'sale_id'],
        'idx_product_returns_inventory_item_id'    => ['product_returns', 'inventory_item_id'],
        'idx_product_returns_customer_id'          => ['product_returns', 'customer_id'],
        'idx_refund_requests_sale_id'              => ['refund_requests', 'sale_id'],
        'idx_refund_requests_customer_id'          => ['refund_requests', 'customer_id'],
        'idx_pre_orders_inventory_item_id'         => ['pre_orders', 'inventory_item_id'],
        'idx_damaged_items_inventory_item_id'      => ['damaged_items', 'inventory_item_id'],
    ];

    public static function pgCreateSql(string $name, string $table, string $column): string
    {
        return sprintf('CREATE INDEX CONCURRENTLY IF NOT EXISTS "%s" ON "%s" ("%s")', $name, $table, $column);
    }

    public static function pgDropSql(string $name): string
    {
        return sprintf('DROP INDEX CONCURRENTLY IF EXISTS "%s"', $name);
    }

    public function up(): void
    {
        foreach (self::INDEXES as $name => [$table, $column]) {
            if (DB::getDriverName() === 'pgsql') {
                // An interrupted CONCURRENTLY build leaves an INVALID index that IF NOT EXISTS would keep.
                if ($this->pgIndexState($name) === false) {
                    DB::statement(self::pgDropSql($name));
                }
                DB::statement(self::pgCreateSql($name, $table, $column));

                continue;
            }

            if (! Schema::hasIndex($table, $name)) {
                Schema::table($table, fn (Blueprint $t) => $t->index($column, $name));
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $name => [$table, $column]) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement(self::pgDropSql($name));

                continue;
            }

            if (Schema::hasIndex($table, $name)) {
                Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
            }
        }
    }

    /** null = index absent, true = valid, false = present but INVALID (current schema only). */
    private function pgIndexState(string $name): ?bool
    {
        $row = DB::selectOne(
            'select i.indisvalid as valid from pg_class c
             join pg_index i on i.indexrelid = c.oid
             join pg_namespace n on n.oid = c.relnamespace
             where c.relname = ? and n.nspname = current_schema()',
            [$name]
        );

        return $row === null ? null : in_array($row->valid, [true, 't', 'true', 1, '1'], true);
    }
};
