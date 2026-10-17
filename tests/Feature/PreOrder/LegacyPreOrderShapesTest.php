<?php

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\PreOrder;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(fn () => Notification::fake());

// Shapes found in production (read-only inspection, 2026-10-17):
//   ID 3  : completed, unpaid, unstocked   (historical / pre-guard)
//   ID 8  : completed, paid,   unstocked   (historical / custom-part style)
//   ID 10 : pending,   paid,   unstocked   (paid early via the old "Collect payment")
function legacyShapeOrder(string $status, bool $paid, bool $withItem): PreOrder
{
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email]);
    $item = $withItem ? InventoryItem::create(['part_name' => 'Legacy Part', 'sku' => 'LG' . uniqid(), 'price' => 400, 'quantity' => 5]) : null;

    return PreOrder::create([
        'customer_id' => $customer->id, 'inventory_item_id' => $item?->id, 'customer_name' => $customer->name,
        'part_name' => 'Legacy Part', 'quantity' => 2, 'price' => 400, 'total' => 800,
        'status' => $status, 'is_paid' => $paid, 'is_stocked' => false,
        'picked_up_at' => $status === 'completed' ? now()->subDays(20) : null,
    ]);
}

function legacySnapshot(PreOrder $o): array
{
    return [$o->fresh()->getAttributes(), $o->inventory_item_id ? InventoryItem::find($o->inventory_item_id)->quantity : null, Sale::count()];
}

dataset('completed legacy shapes', [
    'ID 3 completed unpaid unstocked, catalogue item' => ['completed', false, true],
    'ID 3 completed unpaid unstocked, custom part' => ['completed', false, false],
    'ID 8 completed paid unstocked, catalogue item' => ['completed', true, true],
    'ID 8 completed paid unstocked, custom part' => ['completed', true, false],
]);

test('completed legacy records are untouched by every owner action', function (string $status, bool $paid, bool $withItem) {
    $owner = User::factory()->create(['role' => 'owner']);
    $order = legacyShapeOrder($status, $paid, $withItem);
    $before = legacySnapshot($order);

    foreach (['source', 'stock', 'notify-ready', 'cancel', 'complete'] as $action) {
        $this->actingAs($owner)->post("/preorders/{$order->id}/{$action}");
    }
    $this->actingAs($owner)->post(route('preorders.pay', $order), ['amount_tendered' => 800]);
    $this->actingAs($owner)->post(route('preorders.pickup', $order), ['amount_tendered' => 800])->assertSessionMissing('success');

    expect(legacySnapshot($order))->toEqual($before); // no payment, no sale, no inventory change, no field change
})->with('completed legacy shapes');

test('completed legacy records are untouched by the expiry command', function (string $status, bool $paid, bool $withItem) {
    $order = legacyShapeOrder($status, $paid, $withItem);
    $before = legacySnapshot($order);

    $this->artisan('preorders:expire')->assertSuccessful();
    $this->artisan('reservations:expire')->assertSuccessful();

    expect(legacySnapshot($order))->toEqual($before);
})->with('completed legacy shapes');

test('completed legacy records still render in owner and customer lists as Completed', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $a = legacyShapeOrder('completed', false, true);
    $b = legacyShapeOrder('completed', true, false);

    $html = $this->actingAs($owner)->get(route('preorders.index'))->assertOk()->getContent();
    expect($html)->toContain('preorder-' . $a->id)->and($html)->toContain('preorder-' . $b->id)->and($html)->toContain('Completed')
        ->and($html)->not->toContain(route('preorders.pickup', $a))->and($html)->not->toContain(route('preorders.pickup', $b));

    $this->actingAs($a->customer->user)->get(route('customer.dashboard'))->assertOk()->assertSee('Completed');
});

test('ID 10 shape: paid pending unstocked cannot be picked up before the part is ready', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $order = legacyShapeOrder('pending', true, true);
    $before = legacySnapshot($order);

    $this->actingAs($owner)->post(route('preorders.pickup', $order), ['amount_tendered' => 800])->assertSessionHas('error');
    $this->actingAs($owner)->post(route('preorders.complete', $order))->assertSessionHas('error');

    expect(legacySnapshot($order))->toEqual($before);
    expect($order->fresh()->stage()['key'])->toBe('pending_sourcing');
});

test('ID 10 shape: pickup UI is hidden until ready and shows no extra charge once ready', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $order = legacyShapeOrder('pending', true, true);

    $html = $this->actingAs($owner)->get(route('preorders.index'))->getContent();
    expect($html)->not->toContain(route('preorders.pickup', $order))->and($html)->toContain('Already paid');

    $this->actingAs($owner)->post(route('preorders.stock', $order));
    $html = $this->actingAs($owner)->get(route('preorders.index'))->getContent();
    expect($html)->toContain(route('preorders.pickup', $order))->and($html)->toContain('No additional cash will be collected');
});

test('ID 10 shape: full path source, arrive, pickup creates no sale and deducts stock exactly once', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $order = legacyShapeOrder('pending', true, true);
    $item = $order->inventoryItem;

    $this->actingAs($owner)->post(route('preorders.source', $order))->assertSessionHas('success');
    expect($item->fresh()->quantity)->toBe(5); // sourcing moves nothing

    $this->actingAs($owner)->post(route('preorders.stock', $order))->assertSessionHas('success');
    expect($item->fresh()->quantity)->toBe(3); // allocation, once

    $this->actingAs($owner)->post(route('preorders.pickup', $order), ['amount_tendered' => 0])->assertSessionHas('success');
    $this->actingAs($owner)->post(route('preorders.pickup', $order), ['amount_tendered' => 800]); // double submit

    $fresh = $order->fresh();
    expect($fresh->status)->toBe('completed')->and($fresh->is_paid)->toBeTrue()->and($fresh->picked_up_at)->not->toBeNull();
    expect(Sale::count())->toBe(0);              // paid earlier; Release A creates no second sale
    expect($item->fresh()->quantity)->toBe(3);   // pickup does not deduct again
});

test('ID 10 shape: custom part (no catalogue item) completes the same way without touching any stock', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $order = legacyShapeOrder('pending', true, false);

    $this->actingAs($owner)->post(route('preorders.stock', $order))->assertSessionHas('success');
    $this->actingAs($owner)->post(route('preorders.pickup', $order), ['amount_tendered' => 0])->assertSessionHas('success');

    expect($order->fresh()->status)->toBe('completed')->and(Sale::count())->toBe(0);
});

test('ID 10 shape: a paid pre-order cannot be cancelled by the old path and pay cannot charge again', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $order = legacyShapeOrder('pending', true, true);

    $this->actingAs($owner)->post(route('preorders.pay', $order), ['amount_tendered' => 800])->assertSessionHas('error');

    $this->actingAs($owner)->post(route('preorders.cancel', $order))->assertSessionHas('error');

    expect(Sale::count())->toBe(0)->and($order->fresh()->is_paid)->toBeTrue()->and($order->fresh()->status)->toBe('pending');
});

test('the sourced_at column leaves legacy rows valid: NULL, statuses and flags unchanged', function () {
    foreach ([['completed', false], ['completed', true], ['pending', true]] as [$s, $p]) {
        $o = legacyShapeOrder($s, $p, true)->fresh();
        expect($o->sourced_at)->toBeNull()->and($o->status)->toBe($s)->and($o->is_paid)->toBe($p)->and($o->is_stocked)->toBeFalse();
    }
});
