<?php

use App\Models\InventoryItem;
use App\Models\Motorcycle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function bmPart(string $name, string $model, array $extra = []): InventoryItem
{
    return InventoryItem::create(array_merge([
        'category' => 'Brakes',
        'part_name' => $name,
        'brand' => 'Generic',
        'model' => $model,
        'price' => 100,
        'quantity' => 5,
        'low_stock_threshold' => 1,
    ], $extra));
}

function bmBrowse(array $q): array
{
    $r = test()->get(route('browse', $q))->assertOk();

    return collect($r->viewData('items')->items())->pluck('part_name')->sort()->values()->all();
}

function bmChecker(array $q): array
{
    return collect(test()->getJson(route('compatibility.check', $q))->assertOk()->json())
        ->pluck('part_name')->sort()->values()->all();
}

function bmSeed(): void
{
    bmPart('Click Pad A', 'Click 125');
    bmPart('Click Pad B', 'Click 125');
    bmPart('Variant Pad', 'Click 125 I');   // separate catalogue entry, never folded in
    bmPart('General Click Pad', 'Click');   // family line
    bmPart('Wave Pad', 'Wave 125');
    bmPart('Texty Pad', 'Click125/FI');     // not an explicit alias: never guessed
    bmPart('Gone Pad', 'Click 125', ['quantity' => 0]); // out of stock stays hidden on Browse
}

test('flag ON: browse model filter uses the same inventory-sourced rules as the checker', function () {
    config(['compatibility.inventory_sourced' => true]);
    bmSeed();

    expect(bmBrowse(['model' => 'Click 125']))->toBe(['Click Pad A', 'Click Pad B']);
    expect(bmBrowse(['model' => 'click   125']))->toBe(['Click Pad A', 'Click Pad B']); // case/space-insensitive
    expect(bmBrowse(['model' => 'Click 125 I']))->toBe(['Variant Pad']);
    expect(bmBrowse(['model' => 'Click (general)']))->toBe(['General Click Pad']);
});

test('flag ON: unknown or partial model text matches nothing and is never guessed', function () {
    config(['compatibility.inventory_sourced' => true]);
    bmSeed();

    expect(bmBrowse(['model' => 'Click 12']))->toBe([]);
    expect(bmBrowse(['model' => 'Nonexistent 999']))->toBe([]);
    expect(bmBrowse(['model' => 'Click125/FI']))->toBe([]);
});

test('flag ON: browse and the dedicated checker agree, including owner fitment overrides', function () {
    config(['compatibility.inventory_sourced' => true]);
    bmSeed();
    $fitted = bmPart('Fitted Elsewhere Pad', 'No Model');
    $fitted->motorcycles()->attach(Motorcycle::create(['brand' => 'Honda', 'model' => 'Click 125', 'year' => '2020'])->id);

    $browse = bmBrowse(['model' => 'Click 125']);
    $checker = bmChecker(['make' => 'Honda', 'model' => 'Click 125']);

    // Same matching rules. The checker also lists out-of-stock parts (existing behaviour);
    // Browse shows in-stock only, so compare after removing the one out-of-stock part.
    expect($browse)->toBe(['Click Pad A', 'Click Pad B', 'Fitted Elsewhere Pad'])
        ->and(array_values(array_diff($checker, ['Gone Pad'])))->toBe($browse);
});

test('flag OFF: legacy fitment-only browse behaviour is unchanged', function () {
    config(['compatibility.inventory_sourced' => false]);
    bmSeed();

    expect(bmBrowse(['model' => 'Click 125']))->toBe([]); // free-text model column is not consulted

    $fitted = bmPart('Fitted Pad', 'No Model');
    $fitted->motorcycles()->attach(Motorcycle::create(['brand' => 'Honda', 'model' => 'Click 125'])->id);

    expect(bmBrowse(['model' => 'Click 125']))->toBe(['Fitted Pad']);
});

test('model suggestions follow the mode: catalogue when ON, fitment records when OFF', function () {
    Motorcycle::create(['brand' => 'Honda', 'model' => 'Cliff Test']);

    config(['compatibility.inventory_sourced' => false]);
    $off = collect($this->getJson(route('inventory.suggestions', ['field' => 'model', 'q' => 'Cli']))->json())->pluck('value')->all();
    expect($off)->toBe(['Cliff Test']);

    config(['compatibility.inventory_sourced' => true]);
    $on = collect($this->getJson(route('inventory.suggestions', ['field' => 'model', 'q' => 'Cli']))->json())->pluck('value')->all();
    expect($on)->toContain('Click 125')->toContain('Click (general)')->toContain('Click 125 I')
        ->not->toContain('Cliff Test')
        ->and(count($on))->toBeLessThanOrEqual(12);
    expect($this->getJson(route('inventory.suggestions', ['field' => 'model', 'q' => 'zzzz']))->json())->toBe([]);
});

test('keyword search form retains the selected model, escaped', function () {
    config(['compatibility.inventory_sourced' => true]);
    bmSeed();

    $html = $this->get(route('browse', ['model' => 'Click 125', 'search' => 'pad']))->assertOk()->getContent();
    expect($html)->toContain('<input type="hidden" name="model" value="Click 125">');

    $evil = $this->get(route('browse', ['model' => 'Click 125"><script>x</script>']))->assertOk()->getContent();
    expect($evil)->not->toContain('<script>x</script>')->toContain('&lt;script&gt;');

    // Keyword + model intersect.
    expect(bmBrowse(['model' => 'Click 125', 'search' => 'pad']))->toBe(['Click Pad A', 'Click Pad B']);
    expect(bmBrowse(['model' => 'Click 125', 'search' => 'Wave']))->toBe([]);
});

test('clear filters link resets to the bare browse page', function () {
    config(['compatibility.inventory_sourced' => true]);
    bmSeed();

    $html = $this->get(route('browse', ['model' => 'Click 125']))->assertOk()->getContent();
    expect($html)->toContain('href="' . route('browse') . '"');

    expect(bmBrowse([]))->toContain('Wave Pad')->toContain('Texty Pad');
});

test('flag ON: category, sort and pagination still work together with the model filter', function () {
    config(['compatibility.inventory_sourced' => true]);
    foreach (range(1, 14) as $i) {
        bmPart("Pad $i", 'Click 125', ['price' => $i * 10]);
    }
    bmPart('Oil One', 'Click 125', ['category' => 'Engine', 'price' => 5]);
    bmPart('Other Model Pad', 'Wave 125');

    $r = $this->get(route('browse', ['model' => 'Click 125', 'sort' => 'high_low']))->assertOk();
    $page1 = $r->viewData('items');
    expect($page1->total())->toBe(15)->and($page1->items()[0]->part_name)->toBe('Pad 14');

    $r2 = $this->get(route('browse', ['model' => 'Click 125', 'sort' => 'high_low', 'page' => 2]))->assertOk();
    expect(count($r2->viewData('items')->items()))->toBe(3);
    expect($r->getContent())->toContain('model=Click%20125');

    expect(bmBrowse(['model' => 'Click 125', 'category' => 'Engine']))->toBe(['Oil One']);

    $low = $this->get(route('browse', ['model' => 'Click 125', 'sort' => 'low_high']))->viewData('items')->items()[0]->part_name;
    expect($low)->toBe('Oil One');
});
