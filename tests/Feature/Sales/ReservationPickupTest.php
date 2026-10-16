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
