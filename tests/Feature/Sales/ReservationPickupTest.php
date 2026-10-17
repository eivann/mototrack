<?php

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function pickupSetup(string $ref = 'TXN-PK-1', string $status = 'pending'): array
{
    $owner = User::factory()->create(['role' => 'owner']);
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email]);
    $item = InventoryItem::create(['part_name' => 'Disc', 'sku' => 'D' . $ref, 'price' => 500, 'quantity' => 8]);
    $sale = Sale::create([
        'inventory_item_id' => $item->id, 'customer_id' => $customer->id, 'quantity' => 2, 'price' => 500,
        'total_price' => 1000, 'transaction_reference' => $ref, 'status' => $status,
        'reserved_at' => now(), 'expires_at' => now()->addHours(24),
    ]);

    return [$owner, $user, $sale, $item];
}

test('owner completes a reservation pickup with sufficient cash and the sale is recorded as paid', function () {
    [$owner, , $sale, $item] = pickupSetup();

    $this->actingAs($owner)->withSession(['otp_verified' => true])
        ->post(route('sales.complete', 'TXN-PK-1'), ['amount_tendered' => 1200])
        ->assertSessionHas('success');

    $fresh = $sale->fresh();
    expect($fresh->status)->toBe('completed');
    expect((float) $fresh->change)->toBe(200.0);
    expect($fresh->picked_up_at)->not->toBeNull();
    expect($fresh->payment_confirmed_at)->not->toBeNull();
    expect($item->fresh()->quantity)->toBe(8); // stock was allocated at checkout; no second deduction
});

test('insufficient cash is rejected and nothing is recorded as paid', function () {
    [$owner, , $sale] = pickupSetup();

    $this->actingAs($owner)->withSession(['otp_verified' => true])
        ->post(route('sales.complete', 'TXN-PK-1'), ['amount_tendered' => 400])
        ->assertSessionHasErrors('amount_tendered');

    expect($sale->fresh()->status)->toBe('pending');
    expect($sale->fresh()->payment_confirmed_at)->toBeNull();
});

test('completing twice does not double-record payment', function () {
    [$owner, , $sale] = pickupSetup();
    $post = fn () => $this->actingAs($owner)->withSession(['otp_verified' => true])
        ->post(route('sales.complete', 'TXN-PK-1'), ['amount_tendered' => 1000]);

    $post();
    $firstPaidAt = $sale->fresh()->payment_confirmed_at;
    $this->travel(5)->minutes();
    $post();

    expect($sale->fresh()->payment_confirmed_at->toDateTimeString())->toBe($firstPaidAt->toDateTimeString());
});

test('a cancelled reservation cannot be completed and is not reported as paid', function () {
    [$owner, , $sale] = pickupSetup('TXN-PK-2', 'cancelled');

    $this->actingAs($owner)->withSession(['otp_verified' => true])
        ->post(route('sales.complete', 'TXN-PK-2'), ['amount_tendered' => 1000])
        ->assertSessionHas('error')
        ->assertSessionMissing('success');

    expect($sale->fresh()->status)->toBe('cancelled');
    expect($sale->fresh()->payment_confirmed_at)->toBeNull();
});

test('an expired reservation cannot be completed', function () {
    [$owner, , $sale] = pickupSetup('TXN-PK-3');
    $sale->update(['expires_at' => now()->subMinute()]);

    $this->actingAs($owner)->withSession(['otp_verified' => true])
        ->post(route('sales.complete', 'TXN-PK-3'), ['amount_tendered' => 1000])
        ->assertStatus(400);

    expect($sale->fresh()->status)->toBe('pending');
});

test('a customer cannot complete a reservation at the counter', function () {
    [, $user, $sale] = pickupSetup();

    $this->actingAs($user)->withSession(['otp_verified' => true])
        ->post(route('sales.complete', 'TXN-PK-1'), ['amount_tendered' => 1000])
        ->assertForbidden();

    expect($sale->fresh()->status)->toBe('pending');
});

test('a multi-line reservation completes in one action with one combined cash total and no extra stock movement', function () {
    [$owner, , $sale, $item] = pickupSetup('TXN-PK-ML');
    $second = InventoryItem::create(['part_name' => 'Cable', 'sku' => 'CB-ML', 'price' => 250, 'quantity' => 4]);
    $line2 = Sale::create([
        'inventory_item_id' => $second->id, 'customer_id' => $sale->customer_id, 'quantity' => 1, 'price' => 250,
        'total_price' => 250, 'transaction_reference' => 'TXN-PK-ML', 'status' => 'pending',
        'reserved_at' => now(), 'expires_at' => now()->addHours(24),
    ]);

    // 1000 + 250 = 1250 due: 1000 is not enough for the whole reservation.
    $this->actingAs($owner)->withSession(['otp_verified' => true])
        ->post(route('sales.complete', 'TXN-PK-ML'), ['amount_tendered' => 1000])
        ->assertSessionHasErrors('amount_tendered');
    expect($sale->fresh()->status)->toBe('pending')->and($line2->fresh()->status)->toBe('pending');

    $this->actingAs($owner)->withSession(['otp_verified' => true])
        ->post(route('sales.complete', 'TXN-PK-ML'), ['amount_tendered' => 1300])
        ->assertSessionHas('success');

    expect($sale->fresh()->status)->toBe('completed')->and($line2->fresh()->status)->toBe('completed');
    expect((float) $sale->fresh()->change)->toBe(50.0);
    expect($item->fresh()->quantity)->toBe(8)->and($second->fresh()->quantity)->toBe(4);
    expect(Sale::where('transaction_reference', 'TXN-PK-ML')->count())->toBe(2); // no duplicate revenue rows
});

test('a stale second tab cannot complete a reservation that was already paid or cancelled', function () {
    [$owner, , $sale] = pickupSetup('TXN-PK-TAB');
    $post = fn (float $cash) => $this->actingAs($owner)->withSession(['otp_verified' => true])
        ->post(route('sales.complete', 'TXN-PK-TAB'), ['amount_tendered' => $cash]);

    $post(1000);
    $post(9999)->assertSessionMissing('error'); // idempotent: nothing changes
    expect((float) $sale->fresh()->amount_tendered)->toBe(1000.0);

    [, , $sale2] = pickupSetup('TXN-PK-TAB2', 'cancelled');
    $this->actingAs($owner)->withSession(['otp_verified' => true])
        ->post(route('sales.complete', 'TXN-PK-TAB2'), ['amount_tendered' => 1000])->assertSessionHas('error');
    expect($sale2->fresh()->payment_confirmed_at)->toBeNull();
});

test('pickup after the expiry command ran cannot complete and stock is restored once', function () {
    [$owner, , $sale, $item] = pickupSetup('TXN-PK-EXP');
    $sale->update(['expires_at' => now()->subMinute()]);

    $this->artisan('reservations:expire')->assertSuccessful();
    expect($sale->fresh()->status)->toBe('expired')->and($item->fresh()->quantity)->toBe(10);

    $this->actingAs($owner)->withSession(['otp_verified' => true])
        ->post(route('sales.complete', 'TXN-PK-EXP'), ['amount_tendered' => 1000])
        ->assertSessionHas('error');

    expect($sale->fresh()->status)->toBe('expired')->and($item->fresh()->quantity)->toBe(10);
});

test('admin dashboard shows one Confirm pickup & payment modal per reservation with the combined total', function () {
    [$owner, , $sale] = pickupSetup('TXN-PK-UI');
    $other = InventoryItem::create(['part_name' => 'Grip', 'sku' => 'GR-UI', 'price' => 250, 'quantity' => 4]);
    Sale::create(['inventory_item_id' => $other->id, 'customer_id' => $sale->customer_id, 'quantity' => 1, 'price' => 250,
        'total_price' => 250, 'transaction_reference' => 'TXN-PK-UI', 'status' => 'pending',
        'reserved_at' => now(), 'expires_at' => now()->addHours(24)]);

    $html = $this->actingAs($owner)->withSession(['otp_verified' => true])->get(route('dashboard'))->assertOk()->getContent();

    expect(substr_count($html, route('sales.complete', 'TXN-PK-UI')))->toBe(1)
        ->and($html)->toContain('Confirm pickup &amp; payment')
        ->and($html)->toContain('1,250.00')
        ->and($html)->toContain('name="amount_tendered"')
        ->and($html)->not->toContain('Complete / View');
});

test('sales history uses the same pickup modal instead of a bare confirm dialog', function () {
    [$owner] = pickupSetup('TXN-PK-HIST');

    $html = $this->actingAs($owner)->withSession(['otp_verified' => true])->get(route('sales.history'))->assertOk()->getContent();

    expect($html)->toContain(route('sales.complete', 'TXN-PK-HIST'))
        ->and($html)->toContain('name="amount_tendered"')
        ->and($html)->not->toContain('Confirm payment received and item picked up?');
});
