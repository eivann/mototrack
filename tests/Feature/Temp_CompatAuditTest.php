<?php

use App\Models\InventoryItem;
use App\Models\Motorcycle;
use App\Services\InventoryCompatibilityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// Test 1: Check if inventory-sourced mode resolves Click 125
it('reproduces Click 125 case with inventory-sourced mode', function () {
    config(['compatibility.inventory_sourced' => true]);

    // Create synthetic inventory items with "Click 125" model
    $item1 = InventoryItem::create([
        'category' => 'Parts',
        'part_name' => 'AuditPart-BrakePad',
        'brand' => 'AuditBrand',
        'model' => 'Click 125',
        'price' => 150,
        'quantity' => 5,
    ]);

    $item2 = InventoryItem::create([
        'category' => 'Parts',
        'part_name' => 'AuditPart-AirFilter',
        'brand' => 'AuditBrand',
        'model' => 'Click 125',
        'price' => 200,
        'quantity' => 0,  // Out of stock
    ]);

    $item3 = InventoryItem::create([
        'category' => 'Parts',
        'part_name' => 'AuditPart-ClutchLever',
        'brand' => 'AuditBrand',
        'model' => 'Click125',  // No space variant
        'price' => 180,
        'quantity' => 3,
    ]);

    // Count queries
    DB::enableQueryLog();
    $response = $this->getJson(route('compatibility.check', [
        'make' => 'Honda',
        'model' => 'Click 125',
    ]))->assertOk();

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $results = $response->json();

    dump('=== Test 1: Inventory-sourced Click 125 ===');
    dump('Results count: ' . count($results));
    dump('Query count: ' . count($queries));
    dump('Part names: ' . implode(', ', array_column($results, 'part_name')));

    // Check if Click 125 is in the catalogue
    $resolver = app(InventoryCompatibilityResolver::class);
    $entry = $resolver->findEntry('Honda', 'Click 125');
    dump('Catalogue entry found: ' . ($entry !== null ? 'YES' : 'NO'));
    if ($entry) {
        dump('Aliases: ' . implode(', ', $entry['aliases']));
    }

    expect(count($results))->toBeGreaterThan(0);
    expect(array_column($results, 'part_name'))->toContain('AuditPart-BrakePad');
});

// Test 2: Check if legacy mode (flag OFF) resolves Click 125
it('reproduces Click 125 case with legacy mode (flag OFF)', function () {
    config(['compatibility.inventory_sourced' => false]);

    // Create a Motorcycle record and part_compatibility link
    $motorcycle = Motorcycle::create([
        'brand' => 'Honda',
        'model' => 'Click 125',
        'year' => null,
    ]);

    $item = InventoryItem::create([
        'category' => 'Parts',
        'part_name' => 'AuditPart-LegacyPart',
        'brand' => 'AuditBrand',
        'model' => 'Click 125',
        'price' => 250,
        'quantity' => 2,
    ]);

    // Create compatibility link
    DB::table('part_compatibility')->insert([
        'inventory_item_id' => $item->id,
        'motorcycle_id' => $motorcycle->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->getJson(route('compatibility.check', [
        'make' => 'Honda',
        'model' => 'Click 125',
    ]))->assertOk();

    $results = $response->json();

    dump('=== Test 2: Legacy mode with motorcycle record ===');
    dump('Results count: ' . count($results));
    dump('Part names: ' . implode(', ', array_column($results, 'part_name')));

    expect(count($results))->toBeGreaterThan(0);
    expect(array_column($results, 'part_name'))->toContain('AuditPart-LegacyPart');
});

// Test 3: Check if legacy mode WITHOUT motorcycle record returns empty
it('shows legacy mode returns empty without motorcycle record', function () {
    config(['compatibility.inventory_sourced' => false]);

    // Create inventory item but NO motorcycle record
    $item = InventoryItem::create([
        'category' => 'Parts',
        'part_name' => 'AuditPart-NoFitment',
        'brand' => 'AuditBrand',
        'model' => 'Click 125',
        'price' => 100,
        'quantity' => 5,
    ]);

    $response = $this->getJson(route('compatibility.check', [
        'make' => 'Honda',
        'model' => 'Click 125',
    ]))->assertOk();

    $results = $response->json();

    dump('=== Test 3: Legacy mode without motorcycle record ===');
    dump('Results count: ' . count($results));

    // This should be empty because there's no fitment record
    expect(count($results))->toBe(0);
});

// Test 4: Check exact-match vs partial-match behavior
it('tests exact match for Click 125 variants', function () {
    config(['compatibility.inventory_sourced' => true]);

    // Create items with various Click 125 spellings
    $variants = [
        ['name' => 'AuditPart-Exact', 'model' => 'Click 125'],
        ['name' => 'AuditPart-NoSpace', 'model' => 'Click125'],
        ['name' => 'AuditPart-Upper', 'model' => 'CLICK 125'],
        ['name' => 'AuditPart-ExtraSpace', 'model' => 'Click  125'],
        ['name' => 'AuditPart-FI', 'model' => 'Click 125 FI'],
        ['name' => 'AuditPart-WrongModel', 'model' => 'Click 150'],
    ];

    foreach ($variants as $v) {
        InventoryItem::create([
            'category' => 'Parts',
            'part_name' => $v['name'],
            'brand' => 'AuditBrand',
            'model' => $v['model'],
            'price' => 100,
            'quantity' => 5,
        ]);
    }

    $response = $this->getJson(route('compatibility.check', [
        'make' => 'Honda',
        'model' => 'Click 125',
    ]))->assertOk();

    $results = $response->json();
    $names = array_column($results, 'part_name');

    dump('=== Test 4: Click 125 variants ===');
    dump('Results count: ' . count($results));
    dump('Matched parts: ' . implode(', ', $names));

    // Should match: Exact (same), NoSpace (alias), Upper (normalized), ExtraSpace (normalized)
    // Should NOT match: FI (different model), WrongModel (different model)
    expect($names)->toContain('AuditPart-Exact');
    expect($names)->toContain('AuditPart-NoSpace');
    expect($names)->toContain('AuditPart-Upper');
    expect($names)->toContain('AuditPart-ExtraSpace');
    expect($names)->not->toContain('AuditPart-FI');
    expect($names)->not->toContain('AuditPart-WrongModel');
});

// Test 5: Check if out-of-stock items are filtered
it('checks if out-of-stock items appear in results', function () {
    config(['compatibility.inventory_sourced' => true]);

    $inStock = InventoryItem::create([
        'category' => 'Parts',
        'part_name' => 'AuditPart-InStock',
        'brand' => 'AuditBrand',
        'model' => 'Click 125',
        'price' => 100,
        'quantity' => 5,
    ]);

    $outOfStock = InventoryItem::create([
        'category' => 'Parts',
        'part_name' => 'AuditPart-OutStock',
        'brand' => 'AuditBrand',
        'model' => 'Click 125',
        'price' => 100,
        'quantity' => 0,
    ]);

    $response = $this->getJson(route('compatibility.check', [
        'make' => 'Honda',
        'model' => 'Click 125',
    ]))->assertOk();

    $results = $response->json();
    $names = array_column($results, 'part_name');

    dump('=== Test 5: Stock filter check ===');
    dump('Results count: ' . count($results));
    dump('Items: ' . implode(', ', $names));

    // Both should appear - compatibility checker does NOT filter by stock
    expect($names)->toContain('AuditPart-InStock');
    expect($names)->toContain('AuditPart-OutStock');
});
