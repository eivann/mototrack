<?php

use App\Models\InventoryItem;
use App\Models\Motorcycle;
use App\Services\CompatibilityMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Owner decision (option A): while COMPAT_INVENTORY_SOURCED is OFF, the ORIGINAL fitment matcher treats
 * "Click125" and "Click 125" as the same model when comparing owner-entered motorcycle fitments. Every other model
 * still needs an exact (case/whitespace-insensitive) match. No data is created or altered by the matcher; the
 * fitment rows here are synthetic test rows.
 */
uses(RefreshDatabase::class);

beforeEach(fn () => config(['compatibility.inventory_sourced' => false]));

function c125Part(string $name, array $fitments): InventoryItem
{
    $item = InventoryItem::create(['category' => 'Parts', 'part_name' => $name, 'brand' => 'FlagOffBrand', 'model' => 'free text is ignored', 'price' => 100, 'quantity' => 5]);
    foreach ($fitments as $model) {
        $item->motorcycles()->attach(Motorcycle::firstOrCreate(['brand' => 'Honda', 'model' => $model], ['year' => '2020'])->id);
    }

    return $item;
}

function c125Check(string $model): array
{
    return array_column(test()->getJson(route('compatibility.check', ['make' => 'Honda', 'model' => $model]))->assertOk()->json(), 'part_name');
}

test('normalizeModel treats Click125 and Click 125 as one model and nothing else', function () {
    $key = fn (string $v) => CompatibilityMatcher::normalizeModel($v);

    expect($key('Click125'))->toBe($key('Click 125'))->and($key('CLICK125'))->toBe($key('click 125'))->and($key('  Click   125 '))->toBe($key('Click125'));
    foreach (['Click 125 FI', 'Click 125 I', 'Click 150', 'Click155', 'Click 1250', 'Click1250', 'Click', 'Honda Click 125', 'Click 12'] as $other) {
        expect($key($other))->not->toBe($key('Click 125'), $other);
    }
    // normalize() itself is unchanged.
    expect(CompatibilityMatcher::normalize('Click125'))->toBe('click125');
});

test('flag off: a part fitted to the Click125 record shows under Click 125, and vice versa', function () {
    c125Part('FO-FittedClick125', ['Click125']);
    c125Part('FO-FittedClickSpace', ['Click 125']);

    expect(c125Check('Click 125'))->toContain('FO-FittedClick125')->toContain('FO-FittedClickSpace');
    expect(c125Check('Click125'))->toContain('FO-FittedClick125')->toContain('FO-FittedClickSpace');
});

test('flag off: different models are never pulled in by the alias', function () {
    c125Part('FO-OnlyFI', ['Click 125 FI']);
    c125Part('FO-OnlyI', ['Click 125 I']);
    c125Part('FO-Only150', ['Click 150']);
    c125Part('FO-Only155', ['Click155']);
    c125Part('FO-Only1250', ['Click 1250']);
    c125Part('FO-Real', ['Click 125']);

    $names = c125Check('Click125');
    expect($names)->toContain('FO-Real');
    foreach (['FO-OnlyFI', 'FO-OnlyI', 'FO-Only150', 'FO-Only155', 'FO-Only1250'] as $other) {
        expect($names)->not->toContain($other);
        expect(c125Check('Click 125'))->not->toContain($other);
    }
    // And the other models still match themselves exactly.
    expect(c125Check('Click 125 FI'))->toContain('FO-OnlyFI')->not->toContain('FO-Real');
    expect(c125Check('Click155'))->toContain('FO-Only155')->not->toContain('FO-Real');
});

test('flag off: free-text inventory model is still ignored (no part appears without an owner fitment)', function () {
    InventoryItem::create(['category' => 'Parts', 'part_name' => 'FO-TextOnly', 'brand' => 'FlagOffBrand', 'model' => 'Click125', 'price' => 100, 'quantity' => 5]);
    Motorcycle::create(['brand' => 'Honda', 'model' => 'Click 125', 'year' => '2020']);

    expect(c125Check('Click 125'))->not->toContain('FO-TextOnly');
});

test('flag off: the model and year dropdowns offer equivalent spellings once and combine their years', function () {
    Motorcycle::create(['brand' => 'Honda', 'model' => 'Click125', 'year' => '2019']);
    Motorcycle::create(['brand' => 'Honda', 'model' => 'Click 125', 'year' => '2021']);
    Motorcycle::create(['brand' => 'Honda', 'model' => 'Click 150', 'year' => '2022']);

    $models = $this->getJson(route('compatibility.options', ['type' => 'models', 'make' => 'Honda']))->assertOk()->json();
    expect($models)->toBe(['Click 125', 'Click 150']);

    $years = $this->getJson(route('compatibility.options', ['type' => 'years', 'make' => 'Honda', 'model' => 'Click125']))->assertOk()->json();
    expect($years)->toContain('2019')->toContain('2021')->not->toContain('2022');
    expect($this->getJson(route('compatibility.options', ['type' => 'years', 'make' => 'Honda', 'model' => 'Click 150']))->json())->toBe(['2022']);
});

test('flag off: a year filter still applies to the aliased model', function () {
    $part = c125Part('FO-Year2019', []);
    $part->motorcycles()->attach(Motorcycle::create(['brand' => 'Honda', 'model' => 'Click125', 'year' => '2019'])->id);

    $res = fn (string $year) => array_column($this->getJson(route('compatibility.check', ['make' => 'Honda', 'model' => 'Click 125', 'year' => $year]))->json(), 'part_name');
    expect($res('2019'))->toContain('FO-Year2019')->and($res('2021'))->not->toContain('FO-Year2019');
});

test('both compatibility modes work independently of each other', function () {
    $fitted = c125Part('FO-Both', ['Click 125']);
    InventoryItem::create(['category' => 'Parts', 'part_name' => 'FO-Both-TextOnly', 'brand' => 'FlagOffBrand', 'model' => 'Click125', 'price' => 100, 'quantity' => 5]);

    // Flag OFF: owner fitments only.
    config(['compatibility.inventory_sourced' => false]);
    expect(c125Check('Click 125'))->toContain('FO-Both')->not->toContain('FO-Both-TextOnly');

    // Flag ON: catalogue + inventory text with its own alias list; the fitment-only part is not required.
    config(['compatibility.inventory_sourced' => true]);
    $on = c125Check('Click 125');
    expect($on)->toContain('FO-Both-TextOnly');

    // Back OFF: unchanged again.
    config(['compatibility.inventory_sourced' => false]);
    expect(c125Check('Click 125'))->toContain('FO-Both')->not->toContain('FO-Both-TextOnly');
});
