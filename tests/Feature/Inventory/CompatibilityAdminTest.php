<?php

use App\Models\InventoryItem;
use App\Models\Motorcycle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function compatOwner(): User
{
    return User::factory()->create(['role' => 'owner']);
}

function compatPart(array $overrides = []): InventoryItem
{
    return InventoryItem::create(array_merge([
        'category' => 'Brakes',
        'part_name' => 'Brake Pads',
        'brand' => 'Generic',
        'model' => 'Standard',
        'cost_price' => 500,
        'price' => 800,
        'quantity' => 10,
        'minimum_stock' => 5,
        'low_stock_threshold' => 5,
        'low_stock_notified' => false,
    ], $overrides));
}

test('owner can view the compatibility management page', function () {
    $this->actingAs(compatOwner())->get(route('compatibility.manage'))
        ->assertStatus(200)
        ->assertViewIs('inventory.compatibility_admin');
});

test('customer is denied the compatibility management page', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    $this->actingAs($customer)->get(route('compatibility.manage'))
        ->assertStatus(403);
});

test('guest cannot reach the compatibility management page', function () {
    $this->get(route('compatibility.manage'))->assertRedirect('/login');
});

test('owner can link multiple motorcycles to one part', function () {
    $item = compatPart();

    $this->actingAs(compatOwner())
        ->post(route('compatibility.store', $item), [
            'brand' => 'Honda',
            'model' => 'TMX 125',
            'year' => '2020',
        ])->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs(compatOwner())
        ->post(route('compatibility.store', $item), [
            'brand' => 'Honda',
            'model' => 'TMX 155',
            'year' => '2022',
        ])->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs(compatOwner())
        ->post(route('compatibility.store', $item), [
            'brand' => 'Yamaha',
            'model' => 'Sniper 150',
            'year' => '2018-2022',
            'notes' => 'Fits both calipers',
        ])->assertRedirect()
        ->assertSessionHas('success');

    expect($item->fresh()->motorcycles()->count())->toBe(3);

    $fitted = $item->motorcycles->map(fn ($m) => "{$m->brand} {$m->model}")->all();
    expect($fitted)->toContain('Honda TMX 125')
        ->and($fitted)->toContain('Honda TMX 155')
        ->and($fitted)->toContain('Yamaha Sniper 150');
});

test('a duplicate fitment is rejected, not duplicated', function () {
    $item = compatPart();

    $this->actingAs(compatOwner())->post(route('compatibility.store', $item), [
        'brand' => 'Honda',
        'model' => 'TMX 125',
        'year' => '2020',
    ])->assertRedirect()->assertSessionHas('success');

    $this->actingAs(compatOwner())->post(route('compatibility.store', $item), [
        'brand' => 'Honda',
        'model' => 'TMX 125',
        'year' => '2020',
    ])->assertSessionHasErrors('fitment');

    expect($item->motorcycles()->count())->toBe(1);
});

test('placeholder and malformed year values are rejected', function () {
    $item = compatPart();

    foreach (['select', 'none', 'null', 'abc', '202', '20201'] as $badYear) {
        $response = $this->actingAs(compatOwner())->post(route('compatibility.store', $item), [
            'brand' => 'Honda',
            'model' => 'TMX 125',
            'year' => $badYear,
        ]);

        $response->assertSessionHasErrors('year');
    }

    expect($item->motorcycles()->count())->toBe(0);
});

test('brand and model are required and cannot be placeholders', function () {
    $item = compatPart();

    $this->actingAs(compatOwner())->post(route('compatibility.store', $item), [
        'brand' => '',
        'model' => 'TMX 125',
    ])->assertSessionHasErrors('brand');

    $this->actingAs(compatOwner())->post(route('compatibility.store', $item), [
        'brand' => 'Honda',
        'model' => '',
    ])->assertSessionHasErrors('model');

    expect($item->motorcycles()->count())->toBe(0);
});

test('owner can update the note on an existing fitment without breaking the link', function () {
    $item = compatPart();
    $this->actingAs(compatOwner())->post(route('compatibility.store', $item), [
        'brand' => 'Honda',
        'model' => 'TMX 125',
        'notes' => 'first note',
    ]);

    $moto = $item->fresh()->motorcycles()->first();

    $this->actingAs(compatOwner())
        ->patch(route('compatibility.update', [$item->id, $moto->id]), ['notes' => 'second note'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($item->fresh()->motorcycles()->first()->pivot->notes)->toBe('second note');
});

test('owner can remove one fitment while other parts keep the shared motorcycle record', function () {
    $item = compatPart();
    $other = compatPart(['part_name' => 'Clutch Shoe']);

    $this->actingAs(compatOwner())->post(route('compatibility.store', $item), [
        'brand' => 'Honda',
        'model' => 'TMX 125',
    ]);

    $moto = Motorcycle::first();

    $this->actingAs(compatOwner())->post(route('compatibility.store', $other), [
        'brand' => 'Honda',
        'model' => 'TMX 125',
    ]);

    $this->actingAs(compatOwner())
        ->delete(route('compatibility.destroy', [$item->id, $moto->id]))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($item->fresh()->motorcycles()->count())->toBe(0);
    expect($other->fresh()->motorcycles()->count())->toBe(1);
    expect(Motorcycle::count())->toBe(1);
});

test('a fitment can only be removed from the part it belongs to', function () {
    $item = compatPart();
    $stranger = compatPart(['part_name' => 'Unlinked Pad']);

    $this->actingAs(compatOwner())->post(route('compatibility.store', $item), [
        'brand' => 'Honda',
        'model' => 'TMX 125',
    ]);

    $moto = Motorcycle::first();

    $this->actingAs(compatOwner())
        ->delete(route('compatibility.destroy', [$stranger->id, $moto->id]))
        ->assertStatus(404);

    expect($item->fresh()->motorcycles()->count())->toBe(1);
});

test('compatibility options returns the makes, models and years on record', function () {
    Motorcycle::create(['brand' => 'Honda', 'model' => 'TMX 125', 'year' => '2020']);
    Motorcycle::create(['brand' => 'Yamaha', 'model' => 'Sniper', 'year' => '2019']);

    $response = $this->actingAs(compatOwner())->getJson(route('compatibility.manage.options'));

    $response->assertStatus(200)
        ->assertJsonPath('brands.0', 'Honda')
        ->assertJsonCount(2, 'brands');
});
