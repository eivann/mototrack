<?php

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\User;
use App\Notifications\StatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function makeCustomer(string $name = 'Test Rider'): array
{
    $user = User::factory()->create(['role' => 'customer', 'name' => $name]);
    $customer = Customer::create([
        'user_id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
    ]);

    return [$user, $customer];
}

/**
 * Build a reservation the way checkout does: one sales row per item sharing a
 * single transaction_reference, stock already decremented.
 */
function makeReservation(Customer $customer, array $items, string $reference, string $status = 'pending'): array
{
    $sales = [];
    $now = now();

    foreach ($items as [$item, $qty]) {
        $sales[] = Sale::create([
            'inventory_item_id' => $item->id,
            'customer_id' => $customer->id,
            'quantity' => $qty,
            'price' => $item->price,
            'total_price' => $item->price * $qty,
            'transaction_reference' => $reference,
            'status' => $status,
            'reserved_at' => $status === 'pending' ? $now : null,
            'expires_at' => $status === 'pending' ? $now->copy()->addHours(24) : null,
        ]);
    }

    return $sales;
}

function makeItem(string $name, float $price, int $quantity): InventoryItem
{
    return InventoryItem::create([
        'part_name' => $name,
        'sku' => strtoupper(str_replace(' ', '-', $name)),
        'price' => $price,
        'quantity' => $quantity,
    ]);
}

// ── Grouped reservation cancellation ───────────────────────

test('customer cancelling one line of a multi-item reservation cancels the whole group and restores all stock', function () {
    Notification::fake();

    [$user, $customer] = makeCustomer();
    $itemA = makeItem('Brake Disc', 500, 10);
    $itemB = makeItem('Chain Sprocket', 800, 10);

    // Stock was already decremented at checkout.
    $itemA->update(['quantity' => 7]);
    $itemB->update(['quantity' => 8]);

    $sales = makeReservation($customer, [[$itemA, 3], [$itemB, 2]], 'TRX-GROUP-1');

    $response = $this->actingAs($user)->post(route('customer.order.cancel', $sales[0]->id));
    $response->assertRedirect();

    // Both lines cancelled — no partial cancellation.
    expect($sales[0]->fresh()->status)->toBe('cancelled');
    expect($sales[1]->fresh()->status)->toBe('cancelled');

    // Both stocks restored exactly once.
    expect($itemA->fresh()->quantity)->toBe(10);
    expect($itemB->fresh()->quantity)->toBe(10);
});

test('legacy per-item cancel route enforces the same grouped rules as the reference route', function () {
    Notification::fake();

    [$user, $customer] = makeCustomer();
    $itemA = makeItem('Brake Disc', 500, 10);
    $itemB = makeItem('Chain Sprocket', 800, 10);

    $sales = makeReservation($customer, [[$itemA, 1], [$itemB, 1]], 'TRX-GROUP-2');

    // Cancelling via the SECOND line's id must still cancel the entire group.
    $this->actingAs($user)
        ->post(route('customer.order.cancel', $sales[1]->id))
        ->assertRedirect();

    expect($sales[0]->fresh()->status)->toBe('cancelled');
    expect($sales[1]->fresh()->status)->toBe('cancelled');
    expect($itemA->fresh()->quantity)->toBe(11);
    expect($itemB->fresh()->quantity)->toBe(11);
});

test('cancelling by reference cancels the whole grouped reservation', function () {
    Notification::fake();

    [$user, $customer] = makeCustomer();
    $itemA = makeItem('Rotor', 300, 5);
    $itemB = makeItem('Pad Set', 200, 5);
    $itemC = makeItem('Oil Filter', 150, 5);

    makeReservation($customer, [[$itemA, 2], [$itemB, 1], [$itemC, 3]], 'TRX-GROUP-3');

    $this->actingAs($user)
        ->post(route('customer.reservation.cancel', 'TRX-GROUP-3'))
        ->assertRedirect();

    expect(Sale::where('transaction_reference', 'TRX-GROUP-3')->where('status', 'cancelled')->count())->toBe(3);
    expect($itemA->fresh()->quantity)->toBe(7);
    expect($itemB->fresh()->quantity)->toBe(6);
    expect($itemC->fresh()->quantity)->toBe(8);
});

test('repeated cancellation of the same reservation does not restore stock twice', function () {
    Notification::fake();

    [$user, $customer] = makeCustomer();
    $item = makeItem('Chain Sprocket', 800, 10);

    $sales = makeReservation($customer, [[$item, 2]], 'TRX-DUP-1');

    $this->actingAs($user)->post(route('customer.order.cancel', $sales[0]->id))->assertRedirect();
    expect($item->fresh()->quantity)->toBe(12);

    // Second attempt is refused and must not move stock again.
    $this->actingAs($user)->post(route('customer.order.cancel', $sales[0]->id));
    expect($item->fresh()->quantity)->toBe(12);
});

test('another customer cannot cancel a reservation they do not own', function () {
    Notification::fake();

    [, $ownerCustomer] = makeCustomer('Owner Rider');
    [$otherUser] = makeCustomer('Other Rider');

    $item = makeItem('Chain Sprocket', 800, 10);
    $sales = makeReservation($ownerCustomer, [[$item, 1]], 'TRX-IDOR-1');

    $this->actingAs($otherUser)
        ->post(route('customer.order.cancel', $sales[0]->id))
        ->assertForbidden();

    // Unchanged: still pending, stock still held.
    expect($sales[0]->fresh()->status)->toBe('pending');
    expect($item->fresh()->quantity)->toBe(10);
});

test('cancellation is rejected when any line in the group is already completed', function () {
    Notification::fake();

    [$user, $customer] = makeCustomer();
    $itemA = makeItem('Brake Disc', 500, 10);
    $itemB = makeItem('Chain Sprocket', 800, 10);

    $sales = makeReservation($customer, [[$itemA, 1], [$itemB, 1]], 'TRX-MIXED-1');

    // Staff completes one line of the group.
    $sales[1]->update(['status' => 'completed', 'payment_confirmed_at' => now(), 'picked_up_at' => now()]);

    $this->actingAs($user)->post(route('customer.order.cancel', $sales[0]->id));

    // Nothing partially changed — the still-pending line must stay pending.
    expect($sales[0]->fresh()->status)->toBe('pending');
    expect($itemA->fresh()->quantity)->toBe(10);
});

test('cancellation is rejected for an expired reservation', function () {
    Notification::fake();

    [$user, $customer] = makeCustomer();
    $item = makeItem('Chain Sprocket', 800, 10);

    $sales = makeReservation($customer, [[$item, 1]], 'TRX-EXPIRED-1');
    $sales[0]->update(['expires_at' => now()->subMinute()]);

    $this->actingAs($user)->post(route('customer.order.cancel', $sales[0]->id));

    expect($sales[0]->fresh()->status)->toBe('pending');
    expect($item->fresh()->quantity)->toBe(10);
});

test('cancelling notifies the customer and the owner', function () {
    Notification::fake();

    [$user, $customer] = makeCustomer();
    $owner = User::factory()->create(['role' => 'owner']);
    $item = makeItem('Chain Sprocket', 800, 10);

    $sales = makeReservation($customer, [[$item, 1]], 'TRX-NOTIFY-1');

    $this->actingAs($user)->post(route('customer.order.cancel', $sales[0]->id));

    Notification::assertSentTo($user, StatusUpdated::class);
    Notification::assertSentTo($owner, StatusUpdated::class);
});

test('reservations:expire cancels a whole multi-item reservation and restores all stock', function () {
    Notification::fake();

    [, $customer] = makeCustomer();
    $itemA = makeItem('Brake Disc', 500, 10);
    $itemB = makeItem('Chain Sprocket', 800, 10);

    $sales = makeReservation($customer, [[$itemA, 2], [$itemB, 3]], 'TRX-EXP-GROUP');
    foreach ($sales as $sale) {
        $sale->update(['expires_at' => now()->subMinute()]);
    }

    $this->artisan('reservations:expire')->assertSuccessful();

    expect($sales[0]->fresh()->status)->toBe('expired');
    expect($sales[1]->fresh()->status)->toBe('expired');
    expect($itemA->fresh()->quantity)->toBe(12);
    expect($itemB->fresh()->quantity)->toBe(13);
});

// ── Dashboard ──────────────────────────────────────────────

test('customer dashboard shows one reservation card per transaction reference', function () {
    Notification::fake();

    [$user, $customer] = makeCustomer();
    $itemA = makeItem('Brake Disc', 500, 10);
    $itemB = makeItem('Chain Sprocket', 800, 10);

    makeReservation($customer, [[$itemA, 1], [$itemB, 1]], 'TRX-CARD-1');
    makeReservation($customer, [[$itemA, 1]], 'TRX-CARD-2');

    $response = $this->actingAs($user)->get(route('customer.dashboard'));
    $response->assertOk();

    // One card per reference, not per line.
    expect($response->viewData('activeReservations'))->toHaveCount(2);
    expect(collect($response->viewData('activeReservations'))->pluck('reference')->all())
        ->toContain('TRX-CARD-1', 'TRX-CARD-2');
});

test('cancelled reservation moves to history and leaves the active list', function () {
    Notification::fake();

    [$user, $customer] = makeCustomer();
    $item = makeItem('Chain Sprocket', 800, 10);

    $sales = makeReservation($customer, [[$item, 1]], 'TRX-HIST-1');

    $this->actingAs($user)->post(route('customer.order.cancel', $sales[0]->id));

    $response = $this->actingAs($user)->get(route('customer.dashboard'));
    $response->assertOk();

    expect(collect($response->viewData('activeReservations'))->pluck('reference')->all())
        ->not->toContain('TRX-HIST-1');
    expect(collect($response->viewData('reservationHistory'))->pluck('reference')->all())
        ->toContain('TRX-HIST-1');
});

test('customer dashboard does not expose another customer reservations', function () {
    Notification::fake();

    [, $otherCustomer] = makeCustomer('Other Rider');
    [$user] = makeCustomer('Viewer Rider');

    $item = makeItem('Chain Sprocket', 800, 10);
    makeReservation($otherCustomer, [[$item, 1]], 'TRX-PRIVATE');

    $response = $this->actingAs($user)->get(route('customer.dashboard'));
    $response->assertOk();
    $response->assertDontSee('TRX-PRIVATE');
});