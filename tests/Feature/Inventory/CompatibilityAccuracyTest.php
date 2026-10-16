<?php

use App\Models\InventoryItem;
use App\Models\Motorcycle;
use App\Services\CompatibilityMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function accPart(string $name, array $extra = []): InventoryItem
{
    return InventoryItem::create(array_merge([
        'category' => 'Brakes',
        'part_name' => $name,
        'cost_price' => 400,
        'price' => 700,
        'quantity' => 5,
        'minimum_stock' => 1,
        'low_stock_threshold' => 1,
    ], $extra));
}

function accBike(string $brand, string $model, ?string $year): Motorcycle
{
    return Motorcycle::create(['brand' => $brand, 'model' => $model, 'year' => $year]);
}

function fit(InventoryItem $part, Motorcycle $bike): void
{
    $part->motorcycles()->attach($bike->id);
}

function check(array $q): array
{
    return test()->getJson(route('compatibility.check', $q))->assertOk()->json();
}

function names(array $rows): array
{
    return collect($rows)->pluck('part_name')->sort()->values()->all();
}

test('exact make, model and in-range year returns the linked part with confirmed status', function () {
    $part = accPart('TMX Brake Pad');
    fit($part, accBike('Honda', 'TMX 155', '2018-2022'));

    $rows = check(['make' => 'Honda', 'model' => 'TMX 155', 'year' => '2020']);

    expect(names($rows))->toBe(['TMX Brake Pad']);
    expect($rows[0]['compatibility_status'])->toBe('confirmed');
    expect($rows[0]['matched_fitment'][0])->toContain('TMX 155');
});

test('an unrelated make or model never returns a part', function () {
    fit(accPart('TMX Brake Pad'), accBike('Honda', 'TMX 155', '2018-2022'));

    expect(check(['make' => 'Yamaha', 'model' => 'TMX 155']))->toBeEmpty();
    expect(check(['make' => 'Honda', 'model' => 'Click 125']))->toBeEmpty();
});

test('model comparison is exact, so TMX 15 does not match TMX 155 but casing and spacing are ignored', function () {
    fit(accPart('TMX Chain'), accBike('Honda', 'TMX 155', '2020'));

    expect(check(['model' => 'TMX 15']))->toBeEmpty();
    expect(names(check(['model' => '  tmx   155 ', 'make' => 'HONDA'])))->toBe(['TMX Chain']);
});

test('year boundaries are inclusive and outside the range returns nothing', function () {
    fit(accPart('Range Part'), accBike('Honda', 'TMX 155', '2018-2022'));

    foreach (['2018', '2022'] as $inside) {
        expect(names(check(['model' => 'TMX 155', 'year' => $inside])))->toBe(['Range Part']);
    }
    foreach (['2017', '2023'] as $outside) {
        expect(check(['model' => 'TMX 155', 'year' => $outside]))->toBeEmpty();
    }
});

test('a single-year fitment matches only that year', function () {
    fit(accPart('Single Year'), accBike('Honda', 'Wave', '2020'));

    expect(names(check(['model' => 'Wave', 'year' => '2020'])))->toBe(['Single Year']);
    expect(check(['model' => 'Wave', 'year' => '2021']))->toBeEmpty();
    expect(check(['model' => 'Wave', 'year' => '202']))->toBeEmpty();
});

test('make, model and year must all hold on the SAME fitment record', function () {
    $part = accPart('Cross Match');
    fit($part, accBike('Honda', 'TMX 155', '2010'));
    fit($part, accBike('Yamaha', 'Sniper', '2020'));

    // Honda exists, 2020 exists, but never together on one record.
    expect(check(['make' => 'Honda', 'year' => '2020']))->toBeEmpty();
    expect(names(check(['make' => 'Yamaha', 'year' => '2020'])))->toBe(['Cross Match']);
});

test('free-text model on the part and Universal labels do not create compatibility', function () {
    accPart('Text Only Part', ['model' => 'TMX 155', 'brand' => 'Honda']);
    accPart('Universal Mirror', ['model' => 'Universal']);
    fit(accPart('Linked Part'), accBike('Honda', 'TMX 155', null));

    expect(names(check(['make' => 'Honda', 'model' => 'TMX 155'])))->toBe(['Linked Part']);
    expect(names(check(['model' => 'Click 125'])))->toBeEmpty();
});

test('a part with no fitment records is not returned for any selected motorcycle', function () {
    accPart('Orphan Part');

    expect(check(['make' => 'Honda']))->toBeEmpty();
    expect(check(['year' => '2020']))->toBeEmpty();
});

test('duplicate fitment rows return the part once', function () {
    $part = accPart('Dup Part');
    fit($part, accBike('Honda', 'TMX 155', '2018-2022'));
    fit($part, accBike('honda', 'tmx 155', '2020'));

    $rows = check(['make' => 'Honda', 'model' => 'TMX 155', 'year' => '2020']);

    expect($rows)->toHaveCount(1);
    expect($rows[0]['matched_fitment'])->toHaveCount(2);
});

test('a fitment record without a year applies to every year of that model', function () {
    fit(accPart('Any Year'), accBike('Honda', 'Beat', null));

    expect(names(check(['model' => 'Beat', 'year' => '2015'])))->toBe(['Any Year']);
});

test('one part linked to several models appears for each of them', function () {
    $part = accPart('Shared Part');
    fit($part, accBike('Honda', 'TMX 155', '2020'));
    fit($part, accBike('Honda', 'Wave 110', '2020'));

    expect(names(check(['model' => 'TMX 155'])))->toBe(['Shared Part']);
    expect(names(check(['model' => 'Wave 110'])))->toBe(['Shared Part']);
    expect(check(['model' => 'Click']))->toBeEmpty();
});

test('searching by part name alone makes no compatibility claim', function () {
    accPart('Orphan Pad');

    $rows = check(['part' => 'Orphan']);

    expect(names($rows))->toBe(['Orphan Pad']);
    expect($rows[0])->not->toHaveKey('compatibility_status');
});

test('browse model filter uses recorded fitment, not the free-text model column', function () {
    fit(accPart('Fitted Pad'), accBike('Honda', 'TMX 155', '2020'));
    accPart('Texty Pad', ['model' => 'TMX 155']);

    $html = $this->get(route('browse', ['model' => 'TMX 155']))->assertOk()->getContent();

    expect($html)->toContain('Fitted Pad')->not->toContain('Texty Pad');
});

test('year options list individual years expanded from ranges without duplicates', function () {
    accBike('Honda', 'TMX 155', '2018-2020');
    accBike('Honda', 'TMX 155', '2019');

    $years = $this->getJson(route('compatibility.options', ['type' => 'years', 'model' => 'TMX 155']))->json();

    expect($years)->toBe(['2020', '2019', '2018']);
});

test('model and make options come only from fitment records', function () {
    accBike('Honda', 'TMX 155', '2020');
    accPart('Texty', ['brand' => 'Suzuki', 'model' => 'Raider']);

    expect($this->getJson(route('compatibility.options', ['type' => 'makes']))->json())->toBe(['Honda']);
    expect($this->getJson(route('compatibility.options', ['type' => 'models', 'make' => 'Honda']))->json())->toBe(['TMX 155']);
});

test('matcher year helpers handle malformed data safely', function () {
    expect(CompatibilityMatcher::yearCovers('abc', '2020'))->toBeFalse();
    expect(CompatibilityMatcher::yearCovers('2022-2018', '2020'))->toBeFalse();
    expect(CompatibilityMatcher::yearCovers('2018-2022', 'abc'))->toBeFalse();
    expect(CompatibilityMatcher::yearCovers('2018-2022', '2021-2024'))->toBeTrue();
});
