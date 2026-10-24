<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SYS4-06 Stage D1: index for sales.sale_header_id (step 3 of 3).
 *
 * PostgreSQL does not index a referencing FK column automatically; without this index every
 * header lookup of its lines, and every RESTRICT check, scans sales. Same strategy as the
 * SYS4-06 Stage A index migration: CREATE INDEX CONCURRENTLY IF NOT EXISTS (does not block
 * writes), outside a transaction, after dropping an INVALID leftover from an interrupted
 * earlier build. Other drivers (SQLite in tests) use an ordinary Schema index.
 *
 * ROLLBACK: DROP INDEX CONCURRENTLY IF EXISTS on this one index only, and only while no sales
 * row is linked to a header. The rollback runs newest-first, so this migration is reverted
 * before the column migration; refusing here (as the column migration also does) keeps a
 * blocked rollback from leaving the database half rolled back with the index already gone.
 */
return new class extends Migration
{
    /** PostgreSQL forbids CREATE/DROP INDEX CONCURRENTLY inside a transaction block. */
    public $withinTransaction = false;

    public const INDEX = 'idx_sales_sale_header_id';

    public static function pgCreateSql(): string
    {
        return sprintf('CREATE INDEX CONCURRENTLY IF NOT EXISTS "%s" ON "sales" ("sale_header_id")', self::INDEX);
    }

    public static function pgDropSql(): string
    {
        return sprintf('DROP INDEX CONCURRENTLY IF EXISTS "%s"', self::INDEX);
    }

    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            if ($this->pgIndexState() === false) {
                DB::statement(self::pgDropSql());
            }
            DB::statement(self::pgCreateSql());

            return;
        }

        if (! Schema::hasIndex('sales', self::INDEX)) {
            Schema::table('sales', fn (Blueprint $t) => $t->index('sale_header_id', self::INDEX));
        }
    }

    public function down(): void
    {
        // Same guard as the column migration, evaluated BEFORE any index is dropped.
        if (Schema::hasColumn('sales', 'sale_header_id')) {
            $linked = DB::table('sales')->whereNotNull('sale_header_id')->count();
            if ($linked > 0) {
                throw new RuntimeException("Refusing to drop ".self::INDEX.": {$linked} sales row(s) are linked to a header, so the rollback of sales.sale_header_id would be refused. Back up and detach them deliberately before rolling back.");
            }
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(self::pgDropSql());

            return;
        }

        if (Schema::hasIndex('sales', self::INDEX)) {
            Schema::table('sales', fn (Blueprint $t) => $t->dropIndex(self::INDEX));
        }
    }

    /** null = absent, true = valid, false = present but INVALID (current schema only). */
    private function pgIndexState(): ?bool
    {
        $row = DB::selectOne(
            'select i.indisvalid as valid from pg_class c
             join pg_index i on i.indexrelid = c.oid
             join pg_namespace n on n.oid = c.relnamespace
             where c.relname = ? and n.nspname = current_schema()',
            [self::INDEX]
        );

        return $row === null ? null : in_array($row->valid, [true, 't', 'true', 1, '1'], true);
    }
};
