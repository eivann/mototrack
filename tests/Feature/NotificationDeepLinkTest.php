<?php

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\PreOrder;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function linkSetup(): array
{
    $owner = User::factory()->create(['role' => 'owner']);
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email]);
    $item = InventoryItem::create(['part_name' => 'Link Part', 'sku' => 'LP' . uniqid(), 'price' => 100, 'quantity' => 5]);
    $pre = PreOrder::create(['customer_id' => $customer->id, 'inventory_item_id' => $item->id, 'customer_name' => $customer->name,
        'part_name' => 'Link Part', 'quantity' => 1, 'price' => 100, 'total' => 100, 'status' => 'pending', 'is_paid' => false, 'is_stocked' => false]);

    return [$owner, $user, $customer, $item, $pre];
}

function lastLink(User $user, ?string $title = null): ?string
{
    $q = $user->fresh()->notifications()->latest();
    $n = $title ? $q->get()->first(fn ($n) => ($n->data['title'] ?? '') === $title) : $q->first();

    return $n?->data['link'] ?? null;
}

test('pre-order arrival notice deep links to the customer Pre-orders tab and the record', function () {
    [$owner, $user, , , $pre] = linkSetup();

    $this->actingAs($owner)->post(route('preorders.stock', $pre));

    $link = lastLink($user, 'Pre-order Arrived — Ready for Pickup');
    expect($link)->toContain('tab=preorders')->and($link)->toEndWith('#preorder-' . $pre->id);
});

test('pickup confirmation notice deep links to the Pre-orders tab', function () {
    [$owner, $user, , , $pre] = linkSetup();
    $pre->update(['is_stocked' => true, 'pickup_expires_at' => now()->addHours(5)]);

    $this->actingAs($owner)->post(route('preorders.pickup', $pre), ['amount_tendered' => 100]);

    expect(lastLink($user, 'Pre-order Picked Up'))->toContain('tab=preorders');
});

test('customer pre-order cancellation links the customer to Pre-orders and the owner to Process Pre-orders', function () {
    [$owner, $user, , , $pre] = linkSetup();

    $this->actingAs($owner)->post(route('preorders.cancel', $pre));

    expect(lastLink($user, 'Pre-order Cancelled'))->toContain('tab=preorders')
        ->and(lastLink($owner, 'Pre-order Cancelled'))->toContain(route('preorders.index'))->toEndWith('#preorder-' . $pre->id);
});

test('pre-order expiry notices deep link to the right place for each audience', function () {
    [$owner, $user, , $item, $pre] = linkSetup();
    $pre->update(['is_stocked' => true, 'pickup_expires_at' => now()->subMinute()]);

    $this->artisan('preorders:expire')->assertSuccessful();

    expect(lastLink($user, 'Pre-order Expired'))->toContain('tab=preorders')
        ->and(lastLink($owner, 'Pre-order Expired'))->toContain('/preorders');
});

test('new customer pre-order request links the owner to that record', function () {
    [$owner, $user, , $item] = linkSetup();
    $item->update(['quantity' => 0]);

    $this->actingAs($user)->post(route('preorder.store'), ['inventory_item_id' => $item->id, 'quantity' => 1, 'part_name' => 'Link Part']);

    $created = PreOrder::latest('id')->first();
    expect(lastLink($owner, 'New Pre-order Request'))->toContain('/preorders')->toEndWith('#preorder-' . $created->id);
});

test('reservation cancellation links to the Reservations tab for customer and owner', function () {
    [$owner, $user, $customer, $item] = linkSetup();
    Sale::create(['inventory_item_id' => $item->id, 'customer_id' => $customer->id, 'quantity' => 1, 'price' => 100, 'total_price' => 100,
        'transaction_reference' => 'TXN-LINK-1', 'status' => 'pending', 'reserved_at' => now(), 'expires_at' => now()->addHours(24)]);

    $this->actingAs($user)->post(route('customer.reservation.cancel', ['reference' => 'TXN-LINK-1']));

    expect(lastLink($user, 'Reservation Cancelled'))->toContain('tab=reservations')->toEndWith('#reservation-TXN-LINK-1')
        ->and(lastLink($owner, 'Reservation Cancelled'))->toContain('tab=reservations');
});

test('repair and refund notices open their own customer tabs', function () {
    $src = file_get_contents(app_path('Http/Controllers/Repair/RepairController.php'))
         . file_get_contents(app_path('Http/Controllers/Admin/RefundController.php'));

    expect($src)->toContain("['tab' => 'repairs']")->and($src)->toContain("['tab' => 'purchases']")
        ->and($src)->not->toContain("'/customer/dashboard'");
});

test('the notifications page View details uses the stored deep link unchanged', function () {
    [$owner, $user, , , $pre] = linkSetup();
    $this->actingAs($owner)->post(route('preorders.stock', $pre));
    $link = lastLink($user, 'Pre-order Arrived — Ready for Pickup');

    $html = $this->actingAs($user)->get(route('notifications.index'))->assertOk()->getContent();

    expect($html)->toContain('href="' . $link . '"')->and($html)->toContain('View details');
});

test('the customer dashboard opens the tab named in the link and ignores unknown tabs', function () {
    [, $user] = linkSetup();

    $html = $this->actingAs($user)->get(route('customer.dashboard', ['tab' => 'preorders']))->assertOk()->getContent();

    expect($html)->toContain("['overview','reservations','repairs','preorders','purchases'].includes(")
        ->and($html)->toContain('id="preorder-');
});
