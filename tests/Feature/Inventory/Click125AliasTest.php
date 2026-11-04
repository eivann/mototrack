<?php

use App\Models\InventoryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Owner decision (ReDemo-2): `Click125` is an approved alias of `Click 125`. Matching stays exact for every other
 * spelling: only the aliases listed in config/compatibility_catalogue.php, compared case/whitespace-insensitively.
 * Synthetic parts only.
 */
uses(RefreshDatabase::class);

function click125Names(string $model = 'Click 125'): array
{
    return array_column(test()->getJson(route('compatibility.check', ['make' => 'Honda', 'model' => $model]))->assertOk()->json(), 'part_name');
}

test('Click125 is an approved alias of Click 125, and other models are not pulled in', function () {
    config(['compatibility.inventory_sourced' => true]);
    foreach ([
        'Alias-Exact' => 'Click 125', 'Alias-NoSpace' => 'Click125', 'Alias-Upper' => 'CLICK 125', 'Alias-ExtraSpace' => 'Click  125',
        'Alias-FI' => 'Click 125 FI', 'Alias-I' => 'Click 125 I', 'Alias-150' => 'Click 150', 'Alias-155' => 'Click155',
        'Alias-Substring' => 'Click 1250', 'Alias-Prefix' => 'Honda Click 125',
    ] as $name => $model) {
        InventoryItem::create(['category' => 'Parts', 'part_name' => $name, 'brand' => 'AliasBrand', 'model' => $model, 'price' => 100, 'quantity' => 5]);
    }

    $names = click125Names();

    expect($names)->toContain('Alias-Exact')->toContain('Alias-NoSpace')->toContain('Alias-Upper')->toContain('Alias-ExtraSpace');
    foreach (['Alias-FI', 'Alias-I', 'Alias-150', 'Alias-155', 'Alias-Substring', 'Alias-Prefix'] as $other) {
        expect($names)->not->toContain($other);
    }
});

test('the alias works one way only: Click125 stays a separate spelling for every other catalogue entry', function () {
    config(['compatibility.inventory_sourced' => true]);
    InventoryItem::create(['category' => 'Parts', 'part_name' => 'Alias-Only125', 'brand' => 'AliasBrand', 'model' => 'Click125', 'price' => 100, 'quantity' => 5]);

    expect(click125Names('Click 125'))->toContain('Alias-Only125')
        ->and(click125Names('Click 150'))->not->toContain('Alias-Only125')
        ->and(click125Names('Click 125 I'))->not->toContain('Alias-Only125');
});
