<?php

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\PreOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function psOwnerGet(array $q = [])
{
    $owner = User::factory()->create(['role' => 'owner']);

    return test()->actingAs($owner)->withSession(['otp_verified' => true])->get(route('preorders.index', $q));
}

function psOrder(array $o = []): PreOrder
{
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create(['user_id' => $user->id, 'name' => 'Cust ' . $user->id, 'email' => 'ps' . $user->id . '@mail.test']);
    $item = InventoryItem::create(['part_name' => 'Base Part', 'sku' => 'PS' . uniqid(), 'price' => 100, 'quantity' => 0]);

    return PreOrder::create(array_merge([
        'customer_id' => $customer->id,
        'inventory_item_id' => $item->id,
        'customer_name' => 'Juan Dela Cruz',
        'part_name' => 'Brake Pad',
        'model' => 'Honda Click 125',
        'quantity' => 1,
        'price' => 100,
        'total' => 100,
        'status' => 'pending',
        'is_paid' => false,
        'is_stocked' => false,
    ], $o));
}

function psIds($response): array
{
    return $response->viewData('preorders')->pluck('id')->all();
}

test('empty search keeps the existing list, order and pagination', function () {
    foreach (range(1, 12) as $i) {
        psOrder(['part_name' => "Part $i"]);
    }
    $r = psOwnerGet()->assertOk();
    expect($r->viewData('preorders')->total())->toBe(12)
        ->and($r->viewData('preorders')->perPage())->toBe(10)
        ->and(psIds($r)[0])->toBeGreaterThan(psIds($r)[1]);
    $r->assertDontSee('No pre-orders match your search.');
});

test('search matches customer, part and model, case-insensitively', function () {
    $a = psOrder(['customer_name' => 'Maria Santos', 'part_name' => 'Chain Kit', 'model' => 'Yamaha NMAX']);
    $b = psOrder(['customer_name' => 'Pedro Reyes', 'part_name' => 'Spark Plug', 'model' => 'Suzuki Raider']);

    expect(psIds(psOwnerGet(['q' => 'MARIA'])))->toBe([$a->id])
        ->and(psIds(psOwnerGet(['q' => 'spark plug'])))->toBe([$b->id])
        ->and(psIds(psOwnerGet(['q' => 'nmax'])))->toBe([$a->id])
        ->and(psIds(psOwnerGet(['q' => 'maria nmax'])))->toBe([$a->id])
        ->and(psIds(psOwnerGet(['q' => 'maria raider'])))->toBe([]);
});

test('numeric and displayed order references match the order id exactly', function () {
    $a = psOrder(['part_name' => 'Alpha', 'model' => 'Mio']);
    $b = psOrder(['part_name' => 'Beta', 'model' => 'Mio']);
    $pad = str_pad((string) $b->id, 4, '0', STR_PAD_LEFT);

    expect(psIds(psOwnerGet(['q' => (string) $b->id])))->toBe([$b->id])
        ->and(psIds(psOwnerGet(['q' => '#' . $pad])))->toBe([$b->id])
        ->and(psIds(psOwnerGet(['q' => $pad])))->toBe([$b->id])
        ->and(psIds(psOwnerGet(['q' => '#' . $a->id])))->toBe([$a->id]);
});

test('a number also still matches text containing it', function () {
    $o = psOrder(['model' => 'Honda Click 125']);
    $other = psOrder(['model' => 'Yamaha Mio']);
    expect(psIds(psOwnerGet(['q' => '125'])))->toBe([$o->id]);
    expect($other->id)->not->toBe(125);
});

test('wildcards and escape characters are literal', function () {
    $pct = psOrder(['part_name' => '50% Off Kit']);
    $us = psOrder(['part_name' => 'A_B Bolt']);
    $plain = psOrder(['part_name' => 'AXB Bolt']);
    $bang = psOrder(['part_name' => 'Hey! Part']);
    $bs = psOrder(['part_name' => 'Back\\slash']);

    expect(psIds(psOwnerGet(['q' => '%'])))->toBe([$pct->id])
        ->and(psIds(psOwnerGet(['q' => 'a_b'])))->toBe([$us->id])
        ->and(psIds(psOwnerGet(['q' => '!'])))->toBe([$bang->id])
        ->and(psIds(psOwnerGet(['q' => '\\'])))->toBe([$bs->id])
        ->and(psIds(psOwnerGet(['q' => "'; DROP TABLE pre_orders; --"])))->toBe([]);
    expect(PreOrder::count())->toBe(5);
    expect($plain->id)->toBeInt();
});

test('stage filter mirrors the derived stage() for every stage', function () {
    $pending = psOrder();
    $awaiting = psOrder(['sourced_at' => now()]);
    $ready = psOrder(['is_stocked' => true, 'sourced_at' => now()]);
    $readyLegacy = psOrder(['is_stocked' => true]);
    $completed = psOrder(['status' => 'completed', 'is_stocked' => true]);
    $cancelled = psOrder(['status' => 'cancelled', 'sourced_at' => now()]);
    $expired = psOrder(['status' => 'expired', 'is_stocked' => true]);

    $expect = [
        'pending_sourcing' => [$pending->id],
        'awaiting_arrival' => [$awaiting->id],
        'ready' => [$readyLegacy->id, $ready->id],
        'completed' => [$completed->id],
        'cancelled' => [$cancelled->id],
        'expired' => [$expired->id],
    ];
    foreach ($expect as $stage => $ids) {
        $got = psIds(psOwnerGet(['stage' => $stage]));
        sort($got);
        sort($ids);
        expect($got)->toBe($ids, "stage $stage");
    }

    // Every returned row really displays that stage.
    foreach (array_keys($expect) as $stage) {
        foreach (psOwnerGet(['stage' => $stage])->viewData('preorders') as $row) {
            expect($row->stage()['key'])->toBe($stage);
        }
    }
});

test('unknown stage is ignored', function () {
    psOrder();
    psOrder(['status' => 'cancelled']);
    expect(psOwnerGet(['stage' => 'bogus'])->viewData('preorders')->total())->toBe(2);
});

test('search and stage combine', function () {
    $a = psOrder(['part_name' => 'Brake Pad', 'sourced_at' => now()]);
    psOrder(['part_name' => 'Brake Pad']);
    psOrder(['part_name' => 'Chain', 'sourced_at' => now()]);

    expect(psIds(psOwnerGet(['q' => 'brake', 'stage' => 'awaiting_arrival'])))->toBe([$a->id]);
});

test('pagination keeps query string and stable order across pages', function () {
    foreach (range(1, 13) as $i) {
        psOrder(['part_name' => "Gasket $i"]);
    }
    psOrder(['part_name' => 'Other']);

    $p1 = psOwnerGet(['q' => 'gasket', 'stage' => 'pending_sourcing']);
    expect($p1->viewData('preorders')->total())->toBe(13)->and(count(psIds($p1)))->toBe(10);
    $p1->assertSee('q=gasket', false)->assertSee('stage=pending_sourcing', false);

    $p2 = psOwnerGet(['q' => 'gasket', 'stage' => 'pending_sourcing', 'page' => 2]);
    expect(count(psIds($p2)))->toBe(3)
        ->and(array_intersect(psIds($p1), psIds($p2)))->toBe([]);
});

test('search term is escaped in the page and form retains values', function () {
    $r = psOwnerGet(['q' => '<script>alert(1)</script>', 'stage' => 'ready']);
    $r->assertOk()->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('No pre-orders match your search.');
    $r->assertSee('value="ready" selected', false);
});

test('no-match message differs from the genuinely empty state', function () {
    psOwnerGet()->assertSee('No pre-orders found.');
    psOrder();
    psOwnerGet(['q' => 'zzzz'])->assertSee('No pre-orders match your search.');
});

test('guests and customers cannot use the owner pre-order search', function () {
    psOrder();
    $this->get(route('preorders.index', ['q' => 'x']))->assertRedirect();
    $cust = User::factory()->create(['role' => 'customer']);
    $this->actingAs($cust)->withSession(['otp_verified' => true])
        ->get(route('preorders.index', ['q' => 'x']))->assertForbidden();
});

test('searching never changes pre-order data', function () {
    $o = psOrder();
    $before = $o->fresh()->toArray();
    psOwnerGet(['q' => 'brake', 'stage' => 'pending_sourcing'])->assertOk();
    expect($o->fresh()->toArray())->toBe($before);
});
