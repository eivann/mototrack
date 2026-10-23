<?php

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['cache.default' => 'array']);
});

function rsOwner(): User
{
    return User::factory()->create(['role' => 'owner']);
}

function rsCustomer(string $name, ?string $phone = null, ?string $email = null): Customer
{
    $user = User::factory()->create(['role' => 'customer']);

    return Customer::create(['user_id' => $user->id, 'name' => $name, 'phone' => $phone, 'email' => $email ?? ('c' . bin2hex(random_bytes(4)) . '@mail.test')]);
}

function rsItem(string $name, float $price = 100): InventoryItem
{
    return InventoryItem::create([
        'category' => 'Parts', 'part_name' => $name, 'brand' => 'Gen', 'model' => 'Qq',
        'price' => $price, 'quantity' => 50, 'low_stock_threshold' => 1,
    ]);
}

function rsLine(?string $ref, ?InventoryItem $item, ?Customer $customer, string $status = 'pending', float $total = 100, array $extra = []): Sale
{
    return Sale::create(array_merge([
        'inventory_item_id' => $item?->id,
        'customer_id' => $customer?->id,
        'quantity' => 1,
        'price' => $total,
        'total_price' => $total,
        'transaction_reference' => $ref,
        'amount_tendered' => 0,
        'change' => 0,
        'status' => $status,
        'reserved_at' => now(),
        'expires_at' => now()->addHours(24),
    ], $extra));
}

function rsDash(array $q = [])
{
    return test()->actingAs(rsOwner())->withSession(['otp_verified' => true])->get(route('dashboard', $q))->assertOk();
}

/** @return array<int,string> distinct reservation keys in the given view list */
function rsKeys($response, string $list = 'pendingReservations'): array
{
    return $response->viewData($list)->map(fn ($r) => $r->transaction_reference ?? 'row-' . $r->id)->unique()->sort()->values()->all();
}

test('search by transaction reference, case-insensitive', function () {
    $c = rsCustomer('Ana');
    rsLine('TRX-ALPHA1', rsItem('Brake Pad'), $c);
    rsLine('TRX-BETA2', rsItem('Chain Set'), $c);

    expect(rsKeys(rsDash(['tab' => 'reservations', 'q' => 'alpha1'])))->toBe(['TRX-ALPHA1']);
    expect(rsKeys(rsDash(['q' => 'TRX-BETA'])))->toBe(['TRX-BETA2']);
    expect(rsKeys(rsDash(['q' => 'trx'])))->toBe(['TRX-ALPHA1', 'TRX-BETA2']);
});

test('search by customer name, phone and email', function () {
    rsLine('R-1', rsItem('Brake Pad'), rsCustomer('Juan Dela Cruz', '09171234567', 'juan@example.test'));
    rsLine('R-2', rsItem('Chain Set'), rsCustomer('Maria Clara', '09998887777', 'maria@shop.test'));

    expect(rsKeys(rsDash(['q' => 'dela cruz'])))->toBe(['R-1']);
    expect(rsKeys(rsDash(['q' => '0917123'])))->toBe(['R-1']);
    expect(rsKeys(rsDash(['q' => 'MARIA@SHOP'])))->toBe(['R-2']);
    expect(rsKeys(rsDash(['q' => 'nobody'])))->toBe([]);
});

test('search by reserved part name', function () {
    $c = rsCustomer('Ana');
    rsLine('R-1', rsItem('Brake Pad'), $c);
    rsLine('R-2', rsItem('Chain Set'), $c);

    expect(rsKeys(rsDash(['q' => 'chain'])))->toBe(['R-2']);
    expect(rsKeys(rsDash(['q' => 'BRAKE pad'])))->toBe(['R-1']);
});

test('multiple terms are all required, may match different lines of one reservation, and extra spaces are ignored', function () {
    $juan = rsCustomer('Juan Dela Cruz');
    $ana = rsCustomer('Ana Reyes');
    rsLine('R-1', rsItem('Brake Pad'), $juan);
    rsLine('R-1', rsItem('Chain Set'), $juan);
    rsLine('R-2', rsItem('Brake Pad'), $ana);

    expect(rsKeys(rsDash(['q' => 'juan brake'])))->toBe(['R-1']);
    expect(rsKeys(rsDash(['q' => '  brake   chain  '])))->toBe(['R-1']); // different lines, same reservation
    expect(rsKeys(rsDash(['q' => "ana\tbrake"])))->toBe(['R-2']);
    expect(rsKeys(rsDash(['q' => 'juan ana'])))->toBe([]);
    expect(rsKeys(rsDash(['q' => 'brake'])))->toBe(['R-1', 'R-2']);
});

test('percent, underscore, exclamation and backslash are literal', function () {
    $c = rsCustomer('Ana');
    foreach ([
        'R-PCT' => 'Oil 10% Pro', 'R-10W' => 'Oil 10W', 'R-UND' => 'Pad_Set', 'R-X' => 'PadXSet',
        'R-BANG' => 'Wow!Bang', 'R-NOBANG' => 'WowBang', 'R-BS' => 'Back\\Slash', 'R-NOBS' => 'Backslash',
    ] as $ref => $part) {
        rsLine($ref, rsItem($part), $c);
    }

    expect(rsKeys(rsDash(['q' => '10%'])))->toBe(['R-PCT']);
    expect(rsKeys(rsDash(['q' => '%'])))->toBe(['R-PCT']);
    expect(rsKeys(rsDash(['q' => '_'])))->toBe(['R-UND']);
    expect(rsKeys(rsDash(['q' => 'pad_set'])))->toBe(['R-UND']);
    expect(rsKeys(rsDash(['q' => 'w!b'])))->toBe(['R-BANG']);
    expect(rsKeys(rsDash(['q' => '!'])))->toBe(['R-BANG']);
    expect(rsKeys(rsDash(['q' => '\\'])))->toBe(['R-BS']);
    expect(rsKeys(rsDash(['q' => 'back\\s'])))->toBe(['R-BS']);
    expect(rsKeys(rsDash(['q' => '%%'])))->toBe([]);
});

test('an empty or blank query behaves exactly like no query (settled list stays at the latest 15)', function () {
    $c = rsCustomer('Ana');
    rsLine('P-1', rsItem('Brake Pad'), $c);
    foreach (range(1, 20) as $i) {
        rsLine("S-$i", rsItem("Settled $i"), $c, 'cancelled');
    }

    $base = rsDash();
    foreach ([['q' => ''], ['q' => '   '], ['tab' => 'reservations']] as $params) {
        $r = rsDash($params);
        expect(rsKeys($r))->toBe(rsKeys($base))
            ->and($r->viewData('settledReservations'))->toHaveCount(15)
            ->and($r->viewData('pendingReservationCount'))->toBe(1)
            ->and($r->viewData('reservationSearch'))->toBeNull();
    }
});

test('a match on one line returns the whole reservation group with its true total', function () {
    $c = rsCustomer('Ana');
    rsLine('R-GRP', rsItem('Brake Pad'), $c, 'pending', 150);
    rsLine('R-GRP', rsItem('Chain Set'), $c, 'pending', 250);
    rsLine('R-GRP', rsItem('Oil Filter'), $c, 'pending', 100);
    rsLine('R-OTHER', rsItem('Spark Plug'), $c, 'pending', 80);

    $r = rsDash(['q' => 'oil filter']);
    $lines = $r->viewData('pendingReservations');

    expect($lines)->toHaveCount(3)
        ->and((float) $lines->sum('total_price'))->toBe(500.0)
        ->and(rsKeys($r))->toBe(['R-GRP']);
    // Check the rendered Reservations tab only (other tabs list the whole inventory).
    $tab = Illuminate\Support\Str::between($r->getContent(), '<!-- Reservations Tab -->', '<!-- Returns & Refunds Tab -->');
    expect($tab)->toContain('x3 • ₱500.00')->toContain('Brake Pad')->toContain('Chain Set')->toContain('Oil Filter')
        ->not->toContain('Spark Plug')->not->toContain('R-OTHER');
});

test('search finds a settled reservation older than the latest-15 cutoff, and the cap applies after filtering', function () {
    $c = rsCustomer('Ana');
    $this->travelTo(now()->subDays(60));
    rsLine('OLD-MATCH', rsItem('Ancient Fork'), $c, 'expired');
    $this->travelBack();
    foreach (range(1, 20) as $i) {
        rsLine("NEW-$i", rsItem("Recent $i"), $c, 'cancelled');
    }

    expect(collect(rsDash()->viewData('settledReservations'))->pluck('transaction_reference'))->not->toContain('OLD-MATCH');

    $r = rsDash(['q' => 'old-match']);
    expect(rsKeys($r, 'settledReservations'))->toBe(['OLD-MATCH']);
    expect($r->viewData('reservationSearch')['settled_groups'])->toBe(1);

    // 60 matching settled reservations: all matched first, then only the newest 50 groups are shown.
    foreach (range(1, 60) as $i) {
        rsLine("BULK-$i", rsItem("Bulk $i"), $c, 'cancelled');
    }
    $bulk = rsDash(['q' => 'bulk']);
    expect(count(rsKeys($bulk, 'settledReservations')))->toBe(50)
        ->and($bulk->viewData('reservationSearch')['settled_groups'])->toBe(60);
    $bulk->assertSee('Showing the 50 most recent');
});

test('soft-deleted lines are excluded from matching, groups and totals', function () {
    $c = rsCustomer('Ana');
    $gone = rsLine('R-SD', rsItem('Ghost Part'), $c, 'pending', 999);
    rsLine('R-SD', rsItem('Real Part'), $c, 'pending', 100);
    rsLine('R-ONLYGONE', rsItem('Ghost Only'), $c, 'pending', 50)->delete();
    $gone->delete();

    expect(rsKeys(rsDash(['q' => 'ghost'])))->toBe([]);
    $r = rsDash(['q' => 'real']);
    expect($r->viewData('pendingReservations'))->toHaveCount(1)
        ->and((float) $r->viewData('pendingReservations')->sum('total_price'))->toBe(100.0);
});

test('lines without a customer or item and lines without a reference do not break search', function () {
    rsLine('R-NOCUST', rsItem('Lonely Pad'), null);
    rsLine('R-NOITEM', null, rsCustomer('Pedro Cruz'));
    $orphan = rsLine(null, rsItem('Refless Pad'), null);

    expect(rsKeys(rsDash(['q' => 'lonely'])))->toBe(['R-NOCUST']);
    expect(rsKeys(rsDash(['q' => 'pedro'])))->toBe(['R-NOITEM']);
    expect(rsKeys(rsDash(['q' => 'r-noitem'])))->toBe(['R-NOITEM']);
    expect(rsKeys(rsDash(['q' => 'refless'])))->toBe(['row-' . $orphan->id]);
    expect(rsKeys(rsDash(['q' => 'zzzz'])))->toBe([]);
});

test('only reservation lists are searched: ordinary completed POS sales never appear', function () {
    $c = rsCustomer('Ana');
    rsLine('POS-DONE', rsItem('Sold Pad'), null, 'completed', 100, ['reserved_at' => null, 'expires_at' => null]);
    rsLine('R-LIVE', rsItem('Sold Pad Two'), $c);
    rsLine('R-REFUND', rsItem('Sold Pad Three'), $c, 'refunded');

    $r = rsDash(['q' => 'sold pad']);
    expect(rsKeys($r))->toBe(['R-LIVE'])
        ->and(rsKeys($r, 'settledReservations'))->toBe([]);
});

test('form preserves the reservations tab and the (escaped) query; clear link resets', function () {
    rsLine('R-1', rsItem('Brake Pad'), rsCustomer('Ana'));

    $html = rsDash(['tab' => 'reservations', 'q' => 'brake'])->getContent();
    expect($html)->toContain('name="tab" value="reservations"')
        ->toContain('name="q" value="brake"')
        ->toContain('Clear search')
        ->toContain(route('dashboard', ['tab' => 'reservations']));

    $xss = rsDash(['tab' => 'reservations', 'q' => '"><script>alert(1)</script>'])->getContent();
    expect($xss)->not->toContain('<script>alert(1)</script>')->toContain('&lt;script&gt;');

    $plain = rsDash(['tab' => 'reservations'])->getContent();
    expect($plain)->not->toContain('Clear search');
});

test('empty search result shows the search-specific empty state while the pending badge stays global', function () {
    $c = rsCustomer('Ana');
    rsLine('R-1', rsItem('Brake Pad'), $c);
    rsLine('R-2', rsItem('Chain Set'), $c);
    rsLine('R-2', rsItem('Oil Filter'), $c);

    $r = rsDash(['q' => 'nomatchatall']);
    $r->assertSee('No active reservations match your search.');
    expect($r->viewData('pendingReservationCount'))->toBe(2); // 2 pending reservations, unaffected by search

    $hit = rsDash(['q' => 'brake']);
    expect($hit->viewData('pendingReservationCount'))->toBe(2)
        ->and($hit->viewData('reservationSearch')['pending_groups'])->toBe(1);
});

test('dashboard statistics are identical with and without a search', function () {
    $c = rsCustomer('Ana');
    rsLine('R-1', rsItem('Brake Pad'), $c);
    rsLine('S-1', rsItem('Chain Set'), $c, 'cancelled');

    $without = rsDash()->viewData('stats');
    Illuminate\Support\Facades\Cache::flush();
    $with = rsDash(['q' => 'brake'])->viewData('stats');

    expect($with)->toEqual($without);
});

test('guests and customers cannot view or search the owner dashboard', function () {
    $ana = rsCustomer('Ana Secret');
    rsLine('R-PRIVATE', rsItem('Secret Part'), $ana);

    $this->get(route('dashboard', ['tab' => 'reservations', 'q' => 'secret']))->assertRedirect(route('login'));

    $customerUser = User::factory()->create(['role' => 'customer']);
    $resp = $this->actingAs($customerUser)->withSession(['otp_verified' => true])
        ->get(route('dashboard', ['tab' => 'reservations', 'q' => 'secret']));
    $resp->assertRedirect(route('customer.dashboard'));
    expect($resp->getContent())->not->toContain('R-PRIVATE')->not->toContain('Ana Secret');

    $followed = $this->actingAs($customerUser)->withSession(['otp_verified' => true])->get(route('customer.dashboard', ['q' => 'secret']))->assertOk();
    expect($followed->getContent())->not->toContain('R-PRIVATE')->not->toContain('Ana Secret')->not->toContain('Secret Part');
});

test('pickup modal and cancellation data still render for matched reservations', function () {
    $c = rsCustomer('Ana');
    rsLine('R-PICK', rsItem('Brake Pad'), $c, 'pending', 300);

    $r = rsDash(['q' => 'r-pick']);
    $r->assertSee(route('sales.complete', 'R-PICK'), false)->assertSee('R-PICK');
});
