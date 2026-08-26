<?php

use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Isolate the pre-order form region from the full rendered page, which also
 * contains layout forms (nav logout, notification-read). The pre-order form is
 * the one posting to /preorder/store.
 */
function preorderFormRegion(string $html): string
{
    $open = strpos($html, 'action="/preorder/store"');
    expect($open)->not->toBeFalse('pre-order form opening tag not found');

    $close = strpos($html, '</form>', $open);
    expect($close)->not->toBeFalse('pre-order form closing tag not found');

    return substr($html, $open, $close - $open);
}

test('pre-order form is not prematurely closed', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $item = InventoryItem::create(['part_name' => 'Brake Pad', 'price' => 500, 'quantity' => 0]);

    $html = $this->actingAs($user)->get(route('preorder.form', $item->id))
        ->assertOk()->getContent();

    // Within the pre-order form's own region there must be no second <form
    // opening (which a stray </form> + reopening would introduce) — the region
    // between the opening and the first following </form> must be self-contained.
    $region = preorderFormRegion($html);
    expect(substr_count($region, '<form'))->toBe(0); // no nested/re-opened form
});

test('reCAPTCHA, honeypot, and submit button live inside the pre-order form', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $item = InventoryItem::create(['part_name' => 'Clutch Cable', 'price' => 300, 'quantity' => 0]);

    $html = $this->actingAs($user)->get(route('preorder.form', $item->id))
        ->assertOk()->getContent();

    // Each critical element must fall inside the pre-order form region — this is
    // exactly what the August 9 audit caught stranded outside a premature </form>.
    $region = preorderFormRegion($html);
    expect($region)->toContain('g-recaptcha');
    expect($region)->toContain('my_extra_field');
    expect($region)->toContain('type="submit"');
});
