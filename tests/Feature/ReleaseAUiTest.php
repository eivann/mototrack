<?php

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('pre-order catalogue shows the existing item picture with a fallback beneath it', function () {
    $user = User::factory()->create(['role' => 'customer']);
    InventoryItem::create(['part_name' => 'Pictured Part', 'sku' => 'IMG1', 'price' => 100, 'quantity' => 0, 'image_path' => 'products/pictured.jpg']);
    InventoryItem::create(['part_name' => 'Plain Part', 'sku' => 'IMG2', 'price' => 100, 'quantity' => 0]);
    InventoryItem::create(['part_name' => 'In Stock Part', 'sku' => 'IMG3', 'price' => 100, 'quantity' => 5, 'image_path' => 'products/instock.jpg']);

    $html = $this->actingAs($user)->get(route('preorder.parts'))->assertOk()->getContent();

    // Existing image path is used (same resolver as the shop), not a new upload location.
    expect($html)->toContain('alt="Pictured Part"')
        ->and($html)->toContain('products/pictured.jpg')
        ->and($html)->toContain('onerror=')
        ->and($html)->toContain('preorder-image-fallback')
        ->and($html)->not->toContain('alt="Plain Part"')   // no picture on record: placeholder only
        ->and($html)->not->toContain('instock.jpg');        // only out-of-stock parts are listed
    expect(substr_count($html, 'Catalog Part'))->toBe(2);  // both listed parts keep a placeholder
});

test('both dashboards keep the Overview icon in a stable wrapper that lucide cannot strip', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $cu = User::factory()->create(['role' => 'customer']);
    Customer::create(['user_id' => $cu->id, 'name' => $cu->name, 'email' => $cu->email]);

    $admin = $this->actingAs($owner)->withSession(['otp_verified' => true])->get(route('dashboard'))->assertOk()->getContent();
    $cust = $this->actingAs($cu)->get(route('customer.dashboard'))->assertOk()->getContent();

    foreach ([$admin, $cust] as $html) {
        expect($html)->toContain('data-nav-icon="overview"')
            ->and($html)->toMatch('/data-nav-icon="overview"><i data-lucide="layout-dashboard"/')
            // the colour binding lives on the wrapper span, not on the <i> lucide replaces
            ->and($html)->not->toContain('<i data-lucide="layout-dashboard" class="w-5 h-5 transition-transform');
    }
});

test('the damaged and return pickers use the dependency-free item search', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    InventoryItem::create(['part_name' => 'Searchable', 'sku' => 'SRCH', 'price' => 100, 'quantity' => 5]);

    $html = $this->actingAs($owner)->get(route('inventory.adjustments.index'))->assertOk()->getContent();

    expect(substr_count($html, 'data-item-search'))->toBeGreaterThanOrEqual(2)
        ->and($html)->not->toContain('tom-select')
        ->and($html)->not->toContain('searchable-select')
        ->and($html)->toContain('[SRCH] Searchable');
});
