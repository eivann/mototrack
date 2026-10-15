<?php

use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function adjustmentOwner(): User
{
    return User::factory()->create(['role' => 'owner']);
}

function adjustmentItem(int $quantity = 20): InventoryItem
{
    return InventoryItem::create([
        'category' => 'Brakes',
        'part_name' => 'Brake Pads',
        'brand' => 'Generic',
        'model' => 'Standard',
        'cost_price' => 500,
        'price' => 800,
        'quantity' => $quantity,
        'minimum_stock' => 5,
        'low_stock_threshold' => 5,
        'low_stock_notified' => false,
    ]);
}

test('each adjustment dialog renders exactly one select per field', function () {
    adjustmentItem();

    $html = $this->actingAs(adjustmentOwner())
        ->get(route('inventory.adjustments.index'))
        ->assertStatus(200)
        ->getContent();

    // Two item pickers (one per dialog), one customer picker, one action picker.
    expect(substr_count($html, 'name="inventory_item_id"'))->toBe(2)
        ->and(substr_count($html, 'name="customer_id"'))->toBe(1)
        ->and(substr_count($html, 'name="action_taken"'))->toBe(1);

    // Every <select> must be closed exactly once — a stray unclosed control is
    // what makes a browser render a second, empty dropdown.
    expect(substr_count($html, '<select '))->toBe(4)
        ->and(substr_count($html, '</select>'))->toBe(4);
});

test('placeholder options are disabled so they cannot be submitted', function () {
    adjustmentItem();

    $html = $this->actingAs(adjustmentOwner())
        ->get(route('inventory.adjustments.index'))
        ->getContent();

    expect($html)->toContain('<option value="" disabled')
        ->and($html)->toContain('Choose an action...');
});

test('the damaged dialog rejects an empty item selection server-side', function () {
    $this->actingAs(adjustmentOwner())
        ->post(route('inventory.adjustments.store_damaged'), [
            'inventory_item_id' => '',
            'quantity' => 1,
        ])
        ->assertSessionHasErrors('inventory_item_id');
});

test('the damaged dialog rejects a non-existent item id', function () {
    $this->actingAs(adjustmentOwner())
        ->post(route('inventory.adjustments.store_damaged'), [
            'inventory_item_id' => 999999,
            'quantity' => 1,
        ])
        ->assertSessionHasErrors('inventory_item_id');
});

test('the returns dialog rejects a placeholder action_taken value', function () {
    $item = adjustmentItem();

    foreach (['', 'choose', 'select', 'none'] as $placeholder) {
        $this->actingAs(adjustmentOwner())
            ->post(route('inventory.adjustments.store_return'), [
                'inventory_item_id' => $item->id,
                'quantity' => 1,
                'action_taken' => $placeholder,
            ])
            ->assertSessionHasErrors('action_taken');
    }

    expect($item->fresh()->quantity)->toBe(20);
});

test('a damaged quantity above available stock is rejected and moves no stock', function () {
    $item = adjustmentItem(3);

    $this->actingAs(adjustmentOwner())
        ->post(route('inventory.adjustments.store_damaged'), [
            'inventory_item_id' => $item->id,
            'quantity' => 10,
        ])
        ->assertSessionHasErrors('quantity');

    expect($item->fresh()->quantity)->toBe(3);
});

test('a valid damaged submission still decrements stock once', function () {
    $item = adjustmentItem(10);

    $this->actingAs(adjustmentOwner())
        ->post(route('inventory.adjustments.store_damaged'), [
            'inventory_item_id' => $item->id,
            'quantity' => 4,
            'notes' => 'Cracked in transit',
        ])
        ->assertSessionHasNoErrors();

    expect($item->fresh()->quantity)->toBe(6);
});

test('a valid restocked return increments stock exactly once', function () {
    $item = adjustmentItem(10);

    $this->actingAs(adjustmentOwner())
        ->post(route('inventory.adjustments.store_return'), [
            'inventory_item_id' => $item->id,
            'quantity' => 2,
            'action_taken' => 'restocked',
        ])
        ->assertSessionHasNoErrors();

    expect($item->fresh()->quantity)->toBe(12);
});

test('a discarded return does not put defective stock back on the shelf', function () {
    $item = adjustmentItem(10);

    $this->actingAs(adjustmentOwner())
        ->post(route('inventory.adjustments.store_return'), [
            'inventory_item_id' => $item->id,
            'quantity' => 2,
            'action_taken' => 'discarded',
        ])
        ->assertSessionHasNoErrors();

    expect($item->fresh()->quantity)->toBe(10);
});
