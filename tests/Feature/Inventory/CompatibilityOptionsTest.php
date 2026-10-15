<?php

use App\Models\InventoryItem;
use App\Models\Motorcycle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function optionsPart(string $brand, string $model): InventoryItem
{
    return InventoryItem::create([
        'category' => 'Brakes',
        'part_name' => 'Brake Pads ' . $model,
        'brand' => $brand,
        'model' => $model,
        'cost_price' => 500,
        'price' => 800,
        'quantity' => 10,
        'minimum_stock' => 5,
        'low_stock_threshold' => 5,
    ]);
}

test('makes are de-duplicated across motorcycle and inventory rows regardless of casing', function () {
    Motorcycle::create(['brand' => 'Honda', 'model' => 'TMX 125', 'year' => '2020']);
    optionsPart('honda', 'TMX 125');
    optionsPart('HONDA', 'Wave');

    $makes = $this->getJson(route('compatibility.options', ['type' => 'makes']))
        ->assertStatus(200)
        ->json();

    $lowered = array_map('strtolower', $makes);

    expect(array_count_values($lowered)['honda'] ?? 0)->toBe(1);
});

test('models are de-duplicated case-insensitively', function () {
    Motorcycle::create(['brand' => 'Honda', 'model' => 'TMX 125', 'year' => '2020']);
    optionsPart('Honda', 'tmx 125');

    $models = $this->getJson(route('compatibility.options', ['type' => 'models']))
        ->assertStatus(200)
        ->json();

    $lowered = array_map('strtolower', $models);

    expect(array_count_values($lowered)['tmx 125'] ?? 0)->toBe(1);
});

test('blank and whitespace-only values never become dropdown options', function () {
    Motorcycle::create(['brand' => 'Honda', 'model' => 'TMX 125', 'year' => '2020']);
    optionsPart('   ', 'Wave');

    $makes = $this->getJson(route('compatibility.options', ['type' => 'makes']))->json();

    foreach ($makes as $make) {
        expect(trim((string) $make))->not->toBe('');
    }
});

test('years are returned without duplicates', function () {
    Motorcycle::create(['brand' => 'Honda', 'model' => 'TMX 125', 'year' => '2020']);
    Motorcycle::create(['brand' => 'Yamaha', 'model' => 'Sniper', 'year' => '2020']);

    $years = $this->getJson(route('compatibility.options', ['type' => 'years']))->json();

    expect($years)->toBe(array_values(array_unique($years)));
});
