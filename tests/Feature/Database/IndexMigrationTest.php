<?php

use App\Models\InventoryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

const IDX_MIGRATION = 'database/migrations/2026_10_24_100000_add_missing_foreign_key_and_reference_indexes.php';

function idxMigration(): object
{
    return require base_path(IDX_MIGRATION);
}

function idxNames(string $table): array
{
    return collect(Schema::getIndexes($table))->pluck('name')->sort()->values()->all();
}

function idxAllSnapshot(): array
{
    $snap = [];
    foreach (Schema::getTables() as $t) {
        $snap[$t['name']] = idxNames($t['name']);
    }
    ksort($snap);

    return $snap;
}

/** Pre-SYS4-06 indexes that must never be touched. */
const IDX_EXISTING = [
    'inventory_items' => ['idx_inventory_items_category', 'idx_inventory_items_model', 'idx_inventory_items_parent_id', 'idx_inventory_items_price', 'inventory_items_sku_unique'],
    'sales' => ['sales_customer_id_index', 'sales_inventory_item_id_index', 'sales_shop_id_index'],
    'pre_orders' => ['idx_preorders_pickup_expiry', 'pre_orders_customer_id_index', 'pre_orders_shop_id_index'],
    'customers' => ['customers_user_id_unique'],
    'notifications' => ['notifications_notifiable_type_notifiable_id_index'],
    'part_compatibility' => ['part_compatibility_inventory_item_id_index', 'part_compatibility_motorcycle_id_index'],
    'audit_logs' => ['audit_logs_auditable_type_auditable_id_index', 'audit_logs_created_at_index'],
];

test('all eight new indexes exist after migrating, each non-unique on the intended column', function () {
    $expected = [
        'idx_sales_transaction_reference' => ['sales', 'transaction_reference'],
        'idx_product_returns_sale_id' => ['product_returns', 'sale_id'],
        'idx_product_returns_inventory_item_id' => ['product_returns', 'inventory_item_id'],
        'idx_product_returns_customer_id' => ['product_returns', 'customer_id'],
        'idx_refund_requests_sale_id' => ['refund_requests', 'sale_id'],
        'idx_refund_requests_customer_id' => ['refund_requests', 'customer_id'],
        'idx_pre_orders_inventory_item_id' => ['pre_orders', 'inventory_item_id'],
        'idx_damaged_items_inventory_item_id' => ['damaged_items', 'inventory_item_id'],
    ];

    expect(idxMigration()::INDEXES)->toBe($expected);
    foreach ($expected as $name => [$table, $column]) {
        $idx = collect(Schema::getIndexes($table))->firstWhere('name', $name);
        expect($idx)->not->toBeNull($name)
            ->and($idx['columns'])->toBe([$column])
            ->and($idx['unique'])->toBeFalse()
            ->and($idx['primary'])->toBeFalse();
    }
});

test('existing indexes remain intact', function () {
    foreach (IDX_EXISTING as $table => $names) {
        expect(array_values(array_intersect($names, idxNames($table))))->toBe($names, $table);
    }
});

test('re-running up() is idempotent and creates no duplicate indexes', function () {
    $before = idxAllSnapshot();

    $m = idxMigration();
    $m->up();
    $m->up();

    expect(idxAllSnapshot())->toBe($before);
    foreach (['sales', 'product_returns', 'refund_requests', 'pre_orders', 'damaged_items'] as $table) {
        $names = collect(Schema::getIndexes($table))->map(fn ($i) => $i['name'])->all();
        expect($names)->toBe(array_values(array_unique($names)));
    }
});

test('down() removes only the eight new indexes and up() restores them', function () {
    $before = idxAllSnapshot();
    $m = idxMigration();

    $m->down();
    $after = idxAllSnapshot();
    $removed = [];
    foreach ($before as $table => $names) {
        $removed = array_merge($removed, array_values(array_diff($names, $after[$table])));
        expect(array_values(array_diff($after[$table], $names)))->toBe([], $table); // nothing else changed
    }
    sort($removed);
    $new = array_keys($m::INDEXES);
    sort($new);
    expect($removed)->toBe($new);

    $m->down(); // rolling back twice is harmless
    $m->up();
    expect(idxAllSnapshot())->toBe($before);
});

test('existing data is unchanged by down() and up()', function () {
    $m = idxMigration();
    $m->down();

    $item = InventoryItem::create(['part_name' => 'Chain', 'sku' => 'IDX-1', 'price' => 100, 'quantity' => 5]);
    $customerId = DB::table('customers')->insertGetId(['name' => 'Juan', 'email' => 'j@x.test', 'created_at' => now(), 'updated_at' => now()]);
    $saleId = DB::table('sales')->insertGetId([
        'inventory_item_id' => $item->id, 'customer_id' => $customerId, 'quantity' => 1, 'price' => 100,
        'total_price' => 100, 'transaction_reference' => 'TRX-IDX-1', 'status' => 'completed',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('product_returns')->insert(['inventory_item_id' => $item->id, 'customer_id' => $customerId, 'sale_id' => $saleId, 'quantity' => 1, 'action_taken' => 'refund', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('damaged_items')->insert(['inventory_item_id' => $item->id, 'quantity' => 1, 'created_at' => now(), 'updated_at' => now()]);

    $tables = ['inventory_items', 'customers', 'sales', 'product_returns', 'damaged_items'];
    $snapshot = fn () => collect($tables)->mapWithKeys(fn ($t) => [$t => DB::table($t)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all()])->all();
    $before = $snapshot();

    $m->up();

    expect($snapshot())->toBe($before)->and($before['sales'])->toHaveCount(1);
});

test('lookups on the indexed columns use the new indexes', function () {
    if (DB::getDriverName() !== 'sqlite') {
        $this->markTestSkipped('Query plan check is SQLite-specific.');
    }

    $plan = collect(DB::select("EXPLAIN QUERY PLAN select * from sales where transaction_reference = 'TRX-X'"))->pluck('detail')->implode(' ');
    expect($plan)->toContain('idx_sales_transaction_reference');

    $plan = collect(DB::select('EXPLAIN QUERY PLAN select sum(quantity) from product_returns where sale_id = 1'))->pluck('detail')->implode(' ');
    expect($plan)->toContain('idx_product_returns_sale_id');
});

test('PostgreSQL strategy: non-transactional, concurrent, idempotent SQL', function () {
    $m = idxMigration();
    $src = file_get_contents(base_path(IDX_MIGRATION));

    expect($m->withinTransaction)->toBeFalse()
        ->and($m::pgCreateSql('idx_sales_transaction_reference', 'sales', 'transaction_reference'))
        ->toBe('CREATE INDEX CONCURRENTLY IF NOT EXISTS "idx_sales_transaction_reference" ON "sales" ("transaction_reference")')
        ->and($m::pgDropSql('idx_sales_transaction_reference'))
        ->toBe('DROP INDEX CONCURRENTLY IF EXISTS "idx_sales_transaction_reference"')
        // Invalid leftovers from an interrupted concurrent build are cleaned before re-creating.
        ->and($src)->toContain('indisvalid')
        // Never a unique or destructive statement.
        ->and($src)->not->toContain('CREATE UNIQUE')
        ->and($src)->not->toContain('DROP TABLE')
        ->and($src)->not->toContain('dropColumn');
});

test('grouped sales lookups, owner sales history and reservation search keep working', function () {
    $a = InventoryItem::create(['part_name' => 'Brake Pad', 'sku' => 'IDX-2', 'price' => 50, 'quantity' => 10]);
    $b = InventoryItem::create(['part_name' => 'Spark Plug', 'sku' => 'IDX-3', 'price' => 20, 'quantity' => 10]);
    $owner = \App\Models\User::factory()->create(['role' => 'owner']);

    app(\App\Actions\CommitOrder::class)->execute(
        [['id' => $a->id, 'quantity' => 1], ['id' => $b->id, 'quantity' => 2]],
        'TRX-IDX-GROUP', null, 'Out of stock: ',
        fn ($first, $total) => $first ? (float) $total : 0.0, fn ($first, $total) => 0.0, false, 'completed'
    );

    expect(\App\Models\Sale::where('transaction_reference', 'TRX-IDX-GROUP')->count())->toBe(2);

    $this->actingAs($owner)->withSession(['otp_verified' => true])->get('/sales/history')->assertOk();
    $this->actingAs($owner)->withSession(['otp_verified' => true])->get('/sales/TRX-IDX-GROUP/receipt')->assertOk();
    $saleId = \App\Models\Sale::where('transaction_reference', 'TRX-IDX-GROUP')->value('id');
    $this->actingAs($owner)->withSession(['otp_verified' => true])->get("/sales/by-id/{$saleId}/receipt")->assertOk();
    $this->actingAs($owner)->withSession(['otp_verified' => true])->get('/dashboard?q=TRX-IDX-GROUP')->assertOk();
});
