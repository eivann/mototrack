<?php

use App\Models\InventoryItem;
use App\Models\Motorcycle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function catOwner(): User
{
    return User::factory()->create(['role' => 'owner']);
}

function catPart(array $overrides = []): InventoryItem
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
    ], $overrides));
}

test('owner can view the motorcycle catalogue page', function () {
    $this->actingAs(catOwner())->get(route('motorcycles.catalogue'))
        ->assertOk()
        ->assertViewIs('inventory.motorcycle_catalogue');
});

test('customer is denied the catalogue page', function () {
    $this->actingAs(User::factory()->create(['role' => 'customer']))
        ->get(route('motorcycles.catalogue'))->assertStatus(403);
});

test('guest cannot reach the catalogue page', function () {
    $this->get(route('motorcycles.catalogue'))->assertRedirect('/login');
});

test('owner can add a make with several models and NO fitment is created', function () {
    $this->actingAs(catOwner())->post(route('motorcycles.catalogue.store'), [
        'brand' => 'Honda',
        'model' => "TMX 125, TMX 155\nClick 125",
    ])->assertRedirect()->assertSessionHas('success');

    expect(Motorcycle::count())->toBe(3);
    expect(Motorcycle::pluck('model')->sort()->values()->all())
        ->toBe(['Click 125', 'TMX 125', 'TMX 155']);
    // Catalogue entry must never imply compatibility.
    expect(\Illuminate\Support\Facades\DB::table('part_compatibility')->count())->toBe(0);
    expect(Motorcycle::first()->inventoryItems()->count())->toBe(0);
});

test('duplicate models under a make are reused, not duplicated (case/space-insensitive)', function () {
    $owner = catOwner();

    $this->actingAs($owner)->post(route('motorcycles.catalogue.store'), [
        'brand' => 'Honda', 'model' => 'TMX 125',
    ]);
    $this->actingAs($owner)->post(route('motorcycles.catalogue.store'), [
        'brand' => 'honda', 'model' => '  tmx   125 ',
    ])->assertRedirect()->assertSessionHas('success');

    expect(Motorcycle::count())->toBe(1);
    // The first spelling on record is kept.
    expect(Motorcycle::first()->brand)->toBe('Honda');
    expect(Motorcycle::first()->model)->toBe('TMX 125');
});

test('the same model with a different year is a distinct catalogue record', function () {
    $owner = catOwner();

    $this->actingAs($owner)->post(route('motorcycles.catalogue.store'), ['brand' => 'Honda', 'model' => 'TMX 155']);
    $this->actingAs($owner)->post(route('motorcycles.catalogue.store'), ['brand' => 'Honda', 'model' => 'TMX 155', 'year' => '2020']);

    expect(Motorcycle::count())->toBe(2);
});

test('a null year and a blank year are treated as the same catalogue record', function () {
    $owner = catOwner();

    $this->actingAs($owner)->post(route('motorcycles.catalogue.store'), ['brand' => 'Honda', 'model' => 'Wave 110']);
    $this->actingAs($owner)->post(route('motorcycles.catalogue.store'), ['brand' => 'Honda', 'model' => 'Wave 110', 'year' => '']);

    expect(Motorcycle::count())->toBe(1);
    expect(Motorcycle::first()->year)->toBeNull();
});

test('a malformed year is rejected', function () {
    $this->actingAs(catOwner())->post(route('motorcycles.catalogue.store'), [
        'brand' => 'Honda', 'model' => 'TMX 125', 'year' => '20',
    ])->assertSessionHasErrors('year');

    expect(Motorcycle::count())->toBe(0);
});

test('brand and model are required', function () {
    $owner = catOwner();

    $this->actingAs($owner)->post(route('motorcycles.catalogue.store'), ['brand' => '', 'model' => 'TMX 125'])
        ->assertSessionHasErrors('brand');
    $this->actingAs($owner)->post(route('motorcycles.catalogue.store'), ['brand' => 'Honda', 'model' => ''])
        ->assertSessionHasErrors('model');

    expect(Motorcycle::count())->toBe(0);
});

test('catalogue models appear in the customer dropdowns even with no fitments', function () {
    Motorcycle::create(['brand' => 'Yamaha', 'model' => 'NMAX 155']);

    expect($this->getJson(route('compatibility.options', ['type' => 'makes']))->json())->toBe(['Yamaha']);
    expect($this->getJson(route('compatibility.options', ['type' => 'models', 'make' => 'Yamaha']))->json())->toBe(['NMAX 155']);
});

test('a catalogue model with no fitments returns no confirmed parts but stays searchable', function () {
    Motorcycle::create(['brand' => 'Yamaha', 'model' => 'NMAX 155']);

    expect($this->getJson(route('compatibility.check', ['make' => 'Yamaha', 'model' => 'NMAX 155']))->assertOk()->json())
        ->toBeEmpty();
    // The catalogue row is retained for the dropdown.
    expect(Motorcycle::where('model', 'NMAX 155')->exists())->toBeTrue();
});

test('owner can remove a catalogue motorcycle that has no fitments', function () {
    $owner = catOwner();
    $m = Motorcycle::create(['brand' => 'Suzuki', 'model' => 'Raider R150']);

    $this->actingAs($owner)->delete(route('motorcycles.catalogue.destroy', $m->id))
        ->assertRedirect()->assertSessionHas('success');

    expect(Motorcycle::count())->toBe(0);
});

test('removing a catalogue motorcycle that still has a fitment is refused', function () {
    $owner = catOwner();
    $part = catPart();
    $m = Motorcycle::create(['brand' => 'Honda', 'model' => 'TMX 125']);
    $part->motorcycles()->attach($m->id);

    $this->actingAs($owner)->delete(route('motorcycles.catalogue.destroy', $m->id))
        ->assertRedirect()->assertSessionHasErrors('catalogue');

    expect(Motorcycle::count())->toBe(1);
    expect($part->fresh()->motorcycles()->count())->toBe(1);
});

test('customer and guest cannot add or delete catalogue records', function () {
    $m = Motorcycle::create(['brand' => 'Honda', 'model' => 'TMX 125']);
    $customer = User::factory()->create(['role' => 'customer']);

    $this->post(route('motorcycles.catalogue.store'), ['brand' => 'X', 'model' => 'Y'])->assertRedirect('/login');
    $this->actingAs($customer)->post(route('motorcycles.catalogue.store'), ['brand' => 'X', 'model' => 'Y'])->assertStatus(403);
    $this->actingAs($customer)->delete(route('motorcycles.catalogue.destroy', $m->id))->assertStatus(403);

    expect(Motorcycle::count())->toBe(1);
});

test('existing confirmed fitments and catalogue coexist; adding catalogue data does not disturb them', function () {
    $owner = catOwner();
    $part = catPart(['part_name' => 'TMX Brake Pad']);

    // Existing Release B fitment flow still works.
    $this->actingAs($owner)->post(route('compatibility.store', $part), [
        'brand' => 'Honda', 'model' => 'TMX 125', 'year' => '2020',
    ])->assertRedirect()->assertSessionHas('success');

    // Owner adds more catalogue-only models.
    $this->actingAs($owner)->post(route('motorcycles.catalogue.store'), [
        'brand' => 'Honda', 'model' => 'TMX 155, Click 125',
    ])->assertRedirect();

    // The confirmed fitment is intact and the customer search still finds it.
    expect($part->fresh()->motorcycles()->count())->toBe(1);
    $rows = $this->getJson(route('compatibility.check', ['make' => 'Honda', 'model' => 'TMX 125', 'year' => '2020']))->json();
    expect(collect($rows)->pluck('part_name')->all())->toBe(['TMX Brake Pad']);
    expect($rows[0]['compatibility_status'])->toBe('confirmed');
});
