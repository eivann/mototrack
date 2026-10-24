<?php

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\SaleHeader;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * SYS4-06 Stage D1: physical header/detail schema (sale_headers + sales.sale_header_id).
 * Runs on in-memory SQLite. SQLite proves the logical schema and migration order; it does
 * NOT prove PostgreSQL behaviour (locks, CONCURRENTLY, decimal precision enforcement).
 */

const SH_MIG_HEADERS = 'database/migrations/2026_10_24_140000_create_sale_headers_table.php';
const SH_MIG_COLUMN = 'database/migrations/2026_10_24_140100_add_sale_header_id_to_sales_table.php';
const SH_MIG_INDEX = 'database/migrations/2026_10_24_140200_add_sale_header_id_index_to_sales_table.php';

function shMigration(string $path): object
{
    return require base_path($path);
}

/** Roll the three D1 migrations back newest-first. */
function shRollbackAll(): void
{
    shMigration(SH_MIG_INDEX)->down();
    shMigration(SH_MIG_COLUMN)->down();
    shMigration(SH_MIG_HEADERS)->down();
}

function shMigrateAll(): void
{
    shMigration(SH_MIG_HEADERS)->up();
    shMigration(SH_MIG_COLUMN)->up();
    shMigration(SH_MIG_INDEX)->up();
}

function shHeader(array $o = []): SaleHeader
{
    static $n = 0;
    $n++;

    return SaleHeader::create(array_merge([
        'transaction_reference' => 'TRX-SH-'.$n,
        'transaction_type' => SaleHeader::TYPE_POS,
        'total_amount' => 250,
    ], $o));
}

function shLine(?int $headerId = null, array $o = []): Sale
{
    static $m = 0;
    $m++;
    $item = InventoryItem::create(['part_name' => 'Part '.$m, 'sku' => 'SH-'.$m, 'price' => 100, 'quantity' => 10]);

    return Sale::create(array_merge([
        'inventory_item_id' => $item->id, 'quantity' => 1, 'price' => 100, 'total_price' => 100,
        'status' => 'completed', 'sale_header_id' => $headerId,
    ], $o));
}

// ── Schema ───────────────────────────────────────────────────────────────────

test('sale_headers has the approved columns, a unique reference and the expected indexes', function () {
    $cols = collect(Schema::getColumns('sale_headers'))->keyBy('name');

    expect($cols->keys()->all())->toBe([
        'id', 'transaction_reference', 'customer_id', 'shop_id', 'transaction_type', 'total_amount',
        'amount_tendered', 'change_amount', 'reserved_at', 'expires_at', 'payment_confirmed_at',
        'picked_up_at', 'created_at', 'updated_at',
    ]);

    // not null: reference, type, total. Everything else optional.
    foreach (['transaction_reference', 'transaction_type', 'total_amount'] as $required) {
        expect($cols[$required]['nullable'])->toBeFalse($required);
    }
    foreach (['customer_id', 'shop_id', 'amount_tendered', 'change_amount', 'reserved_at', 'expires_at', 'payment_confirmed_at', 'picked_up_at'] as $optional) {
        expect($cols[$optional]['nullable'])->toBeTrue($optional);
    }

    // No header-level status: line status stays authoritative.
    expect($cols->has('status'))->toBeFalse();

    $indexes = collect(Schema::getIndexes('sale_headers'));
    $unique = $indexes->first(fn ($i) => $i['columns'] === ['transaction_reference']);
    expect($unique['unique'])->toBeTrue()
        ->and($indexes->contains(fn ($i) => $i['columns'] === ['shop_id'] && ! $i['unique']))->toBeTrue()
        ->and($indexes->contains(fn ($i) => $i['columns'] === ['customer_id'] && ! $i['unique']))->toBeTrue();
});

test('money columns are decimal(12,2), wider than the line columns they summarise', function () {
    // SQLite does not report precision, so the declaration itself is asserted.
    $src = file_get_contents(base_path(SH_MIG_HEADERS));
    foreach (['total_amount', 'amount_tendered', 'change_amount'] as $col) {
        expect($src)->toContain("decimal('{$col}', 12, 2)");
    }

    // The real sales schema is decimal(10,2); the header must never be narrower.
    $lineMigrations = file_get_contents(base_path('database/migrations/2026_04_13_154458_create_sales_table.php'))
        .file_get_contents(base_path('database/migrations/2026_05_01_155414_add_transaction_reference_to_sales_table.php'));
    expect($lineMigrations)->toContain("decimal('total_price', 10, 2)")->toContain("decimal('amount_tendered', 10, 2)");
});

test('sales gains one nullable, restrictive, indexed sale_header_id and keeps every existing column', function () {
    $cols = collect(Schema::getColumns('sales'))->keyBy('name');

    expect($cols['sale_header_id']['nullable'])->toBeTrue();
    foreach ([
        'id', 'inventory_item_id', 'quantity', 'total_price', 'price', 'transaction_reference', 'amount_tendered',
        'change', 'payment_confirmed_at', 'status', 'cancellation_reason', 'picked_up_at', 'reserved_at',
        'expires_at', 'customer_id', 'shop_id', 'deleted_at', 'created_at', 'updated_at',
    ] as $existing) {
        expect($cols->has($existing))->toBeTrue($existing);
    }

    $fk = collect(Schema::getForeignKeys('sales'))->first(fn ($f) => $f['columns'] === ['sale_header_id']);
    expect($fk['foreign_table'])->toBe('sale_headers')
        ->and($fk['foreign_columns'])->toBe(['id'])
        ->and($fk['on_delete'])->toBe('restrict');

    $idx = collect(Schema::getIndexes('sales'))->firstWhere('name', 'idx_sales_sale_header_id');
    expect($idx['columns'])->toBe(['sale_header_id'])->and($idx['unique'])->toBeFalse();
});

test('customer FK on headers is ON DELETE SET NULL; existing line FKs are untouched', function () {
    $fk = collect(Schema::getForeignKeys('sale_headers'))->first(fn ($f) => $f['columns'] === ['customer_id']);
    expect($fk['foreign_table'])->toBe('customers')->and($fk['on_delete'])->toBe('set null');

    foreach (['product_returns', 'refund_requests'] as $table) {
        $saleFk = collect(Schema::getForeignKeys($table))->first(fn ($f) => $f['foreign_table'] === 'sales');
        expect($saleFk)->not->toBeNull($table)->and($saleFk['columns'])->toBe(['sale_id']);
    }
});

// ── Relationships ────────────────────────────────────────────────────────────

test('a header owns many sales lines and each line points back to its header', function () {
    $customer = Customer::create(['name' => 'Ana', 'email' => 'ana@x.test']);
    $header = shHeader(['customer_id' => $customer->id, 'total_amount' => 200, 'amount_tendered' => 300, 'change_amount' => 100]);
    $l1 = shLine($header->id);
    $l2 = shLine($header->id);
    $other = shLine(shHeader()->id);

    expect($header->lines->pluck('id')->sort()->values()->all())->toBe([$l1->id, $l2->id])
        ->and($l1->header->is($header))->toBeTrue()
        ->and($l2->fresh()->header->id)->toBe($header->id)
        ->and($header->customer->is($customer))->toBeTrue()
        ->and($other->header->id)->not->toBe($header->id)
        ->and($header->fresh()->lines)->toHaveCount(2);
});

test('legacy lines may have no header and direct Sale::create stays supported', function () {
    $legacy = shLine(null, ['transaction_reference' => null]);
    expect($legacy->fresh()->sale_header_id)->toBeNull()->and($legacy->header)->toBeNull();

    // A sale created directly, as legacy code and older tests do, still has no header.
    $withRef = shLine(null, ['transaction_reference' => 'TRX-SH-DIRECT']);
    expect($withRef->fresh()->sale_header_id)->toBeNull()->and(SaleHeader::count())->toBe(0);
    // (CommitOrder writes headers from Stage D2A on; see Sales/CommitOrderHeaderTest.)
});

// ── Integrity ────────────────────────────────────────────────────────────────

test('header transaction references are unique', function () {
    shHeader(['transaction_reference' => 'TRX-SH-DUP']);

    expect(fn () => shHeader(['transaction_reference' => 'TRX-SH-DUP']))->toThrow(QueryException::class);
    expect(SaleHeader::where('transaction_reference', 'TRX-SH-DUP')->count())->toBe(1);
});

test('a line cannot point at a header that does not exist', function () {
    expect(fn () => shLine(999999))->toThrow(QueryException::class);
});

test('a header that still has lines cannot be deleted; an empty header can', function () {
    $header = shHeader();
    $line = shLine($header->id);

    expect(fn () => $header->delete())->toThrow(QueryException::class);
    expect(SaleHeader::find($header->id))->not->toBeNull()
        ->and($line->fresh()->sale_header_id)->toBe($header->id);

    $empty = shHeader();
    $empty->delete();
    expect(SaleHeader::find($empty->id))->toBeNull();
});

test('soft-deleting a line keeps its header link', function () {
    $header = shHeader();
    $line = shLine($header->id);
    $line->delete();

    expect(Sale::withTrashed()->find($line->id)->sale_header_id)->toBe($header->id)
        ->and($header->lines)->toHaveCount(0)
        ->and($header->lines()->withTrashed()->count())->toBe(1);
});

test('deleting a customer nulls the header customer without touching the header', function () {
    $customer = Customer::create(['name' => 'Ben', 'email' => 'ben@x.test']);
    $header = shHeader(['customer_id' => $customer->id]);

    DB::table('customers')->where('id', $customer->id)->delete();

    expect($header->fresh()->customer_id)->toBeNull()->and(SaleHeader::find($header->id))->not->toBeNull();
});

// ── Migration behaviour ──────────────────────────────────────────────────────

test('migrating over existing sales leaves every row and id untouched with a NULL header', function () {
    shRollbackAll();
    expect(Schema::hasTable('sale_headers'))->toBeFalse()->and(Schema::hasColumn('sales', 'sale_header_id'))->toBeFalse();

    $item = InventoryItem::create(['part_name' => 'Old', 'sku' => 'SH-OLD', 'price' => 80, 'quantity' => 3]);
    foreach ([['TRX-OLD-A', 'completed'], ['TRX-OLD-A', 'refunded'], [null, 'completed'], ['TRX-OLD-B', 'pending']] as [$ref, $status]) {
        DB::table('sales')->insert([
            'inventory_item_id' => $item->id, 'quantity' => 1, 'price' => 80, 'total_price' => 80,
            'transaction_reference' => $ref, 'status' => $status, 'amount_tendered' => 100, 'change' => 20,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
    $before = DB::table('sales')->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();

    shMigrateAll();

    $after = DB::table('sales')->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
    expect($after)->toHaveCount(4);
    foreach ($after as $i => $row) {
        expect($row['sale_header_id'])->toBeNull();
        unset($row['sale_header_id']);
        expect($row)->toBe($before[$i]);
    }
    expect(SaleHeader::count())->toBe(0);
});

test('rollback removes only the D1 objects, keeps sales rows, and re-migrating restores them', function () {
    $line = shLine(null);
    $before = Schema::getTables();
    $beforeSalesIdx = collect(Schema::getIndexes('sales'))->pluck('name')->sort()->values()->all();

    shRollbackAll();

    expect(Schema::hasTable('sale_headers'))->toBeFalse()
        ->and(Schema::hasColumn('sales', 'sale_header_id'))->toBeFalse()
        ->and(Schema::hasIndex('sales', 'idx_sales_sale_header_id'))->toBeFalse()
        ->and(Sale::find($line->id))->not->toBeNull()
        // all other indexes (including SYS4-06 Stage A) survive
        ->and(Schema::hasIndex('sales', 'idx_sales_transaction_reference'))->toBeTrue();

    shMigrateAll();

    expect(Schema::hasTable('sale_headers'))->toBeTrue()
        ->and(Schema::hasColumn('sales', 'sale_header_id'))->toBeTrue()
        ->and(collect(Schema::getIndexes('sales'))->pluck('name')->sort()->values()->all())->toBe($beforeSalesIdx)
        ->and(count(Schema::getTables()))->toBe(count($before))
        ->and(Sale::find($line->id))->not->toBeNull();
});

test('up() is idempotent', function () {
    shMigrateAll();
    shMigrateAll();

    $names = collect(Schema::getIndexes('sales'))->pluck('name')->all();
    expect($names)->toBe(array_values(array_unique($names)))
        ->and(collect(Schema::getColumns('sales'))->where('name', 'sale_header_id')->count())->toBe(1);
});

test('column rollback refuses while any sales line is linked, and changes nothing', function () {
    $header = shHeader(['transaction_reference' => 'TRX-SH-KEEP', 'total_amount' => 300]);
    $a = shLine($header->id);
    $b = shLine($header->id);
    $unlinked = shLine(null);

    $snapshot = fn () => [
        'links' => DB::table('sales')->orderBy('id')->pluck('sale_header_id', 'id')->all(),
        'headers' => DB::table('sale_headers')->orderBy('id')->get()->map(fn ($r) => (array) $r)->all(),
        'fk' => collect(Schema::getForeignKeys('sales'))->where('columns', ['sale_header_id'])->values()->all(),
        'index' => Schema::hasIndex('sales', 'idx_sales_sale_header_id'),
    ];
    $before = $snapshot();

    expect(fn () => shMigration(SH_MIG_COLUMN)->down())
        ->toThrow(RuntimeException::class, '2 sales row(s) are linked to a header');

    expect($snapshot())->toBe($before)
        ->and(Schema::hasColumn('sales', 'sale_header_id'))->toBeTrue()
        ->and(Sale::find($a->id)->sale_header_id)->toBe($header->id)
        ->and(Sale::find($b->id)->sale_header_id)->toBe($header->id)
        ->and(Sale::find($unlinked->id)->sale_header_id)->toBeNull()
        ->and(SaleHeader::find($header->id)->lines)->toHaveCount(2);

    // The full newest-first rollback is refused and leaves everything exactly as it was.
    expect(fn () => shRollbackAll())->toThrow(RuntimeException::class, 'linked to a header');
    expect($snapshot())->toBe($before)
        ->and(Sale::find($a->id)->sale_header_id)->toBe($header->id)
        ->and(Schema::hasTable('sale_headers'))->toBeTrue();
});

test('index rollback refuses while any sales line is linked and leaves the index intact', function () {
    $header = shHeader();
    $line = shLine($header->id);
    shLine($header->id)->delete(); // a soft-deleted linked line counts too

    $indexBefore = collect(Schema::getIndexes('sales'))->firstWhere('name', 'idx_sales_sale_header_id');
    expect($indexBefore)->not->toBeNull();

    expect(fn () => shMigration(SH_MIG_INDEX)->down())
        ->toThrow(RuntimeException::class, '2 sales row(s) are linked to a header');

    expect(collect(Schema::getIndexes('sales'))->firstWhere('name', 'idx_sales_sale_header_id'))->toBe($indexBefore)
        ->and(Schema::hasColumn('sales', 'sale_header_id'))->toBeTrue()
        ->and(Sale::find($line->id)->sale_header_id)->toBe($header->id);
});

test('a refused full rollback leaves no half-rolled-back state: index, FK, column and headers all remain', function () {
    $header = shHeader(['transaction_reference' => 'TRX-SH-HALF']);
    shLine($header->id);

    $state = fn () => [
        'index' => Schema::hasIndex('sales', 'idx_sales_sale_header_id'),
        'column' => Schema::hasColumn('sales', 'sale_header_id'),
        'fk' => collect(Schema::getForeignKeys('sales'))->contains(fn ($f) => $f['columns'] === ['sale_header_id']),
        'headers' => DB::table('sale_headers')->count(),
        'links' => DB::table('sales')->whereNotNull('sale_header_id')->count(),
    ];

    expect($state())->toBe(['index' => true, 'column' => true, 'fk' => true, 'headers' => 1, 'links' => 1]);

    expect(fn () => shRollbackAll())->toThrow(RuntimeException::class);

    expect($state())->toBe(['index' => true, 'column' => true, 'fk' => true, 'headers' => 1, 'links' => 1]);
});

test('index rollback still works when every link is NULL', function () {
    shHeader();
    shLine(null);

    shMigration(SH_MIG_INDEX)->down();
    expect(Schema::hasIndex('sales', 'idx_sales_sale_header_id'))->toBeFalse()
        ->and(Schema::hasColumn('sales', 'sale_header_id'))->toBeTrue();

    shMigration(SH_MIG_INDEX)->down(); // repeating is harmless
    shMigration(SH_MIG_INDEX)->up();
    expect(Schema::hasIndex('sales', 'idx_sales_sale_header_id'))->toBeTrue();
});

test('column rollback succeeds when every link is NULL, including soft-deleted lines checked', function () {
    shHeader();                       // a header with no lines
    shLine(null);                     // unlinked line
    shLine(null)->delete();           // unlinked, soft-deleted line

    shMigration(SH_MIG_INDEX)->down();
    shMigration(SH_MIG_COLUMN)->down();

    expect(Schema::hasColumn('sales', 'sale_header_id'))->toBeFalse()
        ->and(Sale::withTrashed()->count())->toBe(2);
});

test('a soft-deleted linked line also blocks the column rollback', function () {
    $header = shHeader();
    $line = shLine($header->id);
    $line->delete();

    expect(fn () => shMigration(SH_MIG_COLUMN)->down())->toThrow(RuntimeException::class, '1 sales row(s) are linked');
    expect(Sale::withTrashed()->find($line->id)->sale_header_id)->toBe($header->id);
});

test('rolling back refuses to destroy a populated header table or one still referenced', function () {
    $header = shHeader();
    $line = shLine($header->id);

    // Table still referenced by the sales column.
    expect(fn () => shMigration(SH_MIG_HEADERS)->down())->toThrow(RuntimeException::class, 'still references');
    expect(Schema::hasTable('sale_headers'))->toBeTrue();

    // Linked lines stop both the index rollback and the column rollback.
    expect(fn () => shMigration(SH_MIG_INDEX)->down())->toThrow(RuntimeException::class, 'linked to a header');
    expect(fn () => shMigration(SH_MIG_COLUMN)->down())->toThrow(RuntimeException::class, 'linked to a header');

    // Only after the links are deliberately detached can the column go; the header table is
    // then still protected because it holds rows.
    DB::table('sales')->where('id', $line->id)->update(['sale_header_id' => null]);
    shMigration(SH_MIG_INDEX)->down();
    shMigration(SH_MIG_COLUMN)->down();
    expect(fn () => shMigration(SH_MIG_HEADERS)->down())->toThrow(RuntimeException::class, 'contains rows');
    expect(SaleHeader::count())->toBe(1);
});

test('PostgreSQL index strategy: concurrent, non-transactional, idempotent, scoped to the one index', function () {
    $m = shMigration(SH_MIG_INDEX);
    $src = file_get_contents(base_path(SH_MIG_INDEX));

    expect($m->withinTransaction)->toBeFalse()
        ->and($m::pgCreateSql())->toBe('CREATE INDEX CONCURRENTLY IF NOT EXISTS "idx_sales_sale_header_id" ON "sales" ("sale_header_id")')
        ->and($m::pgDropSql())->toBe('DROP INDEX CONCURRENTLY IF EXISTS "idx_sales_sale_header_id"')
        ->and($src)->toContain('indisvalid')
        ->and($src)->not->toContain('CREATE UNIQUE')
        ->and($src)->not->toContain('DROP TABLE');

    // The table/column migrations stay transactional (default) and run no raw SQL at all.
    foreach ([SH_MIG_HEADERS, SH_MIG_COLUMN] as $path) {
        expect(shMigration($path)->withinTransaction ?? true)->toBeTrue()
            ->and(file_get_contents(base_path($path)))->not->toContain('DB::statement');
    }
});
