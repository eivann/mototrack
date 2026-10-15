<?php

use App\Models\InventoryItem;
use App\Models\User;
use App\Notifications\StatusUpdated;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.twilio.sid' => null,
        'services.twilio.auth_token' => null,
        'services.twilio.number' => null,
        'services.movider.api_key' => null,
        'services.movider.api_secret' => null,
        'services.movider.notify_number' => null,
    ]);
});

function lowStockItem(array $overrides = []): InventoryItem
{
    return InventoryItem::create(array_merge([
        'category' => 'Brakes',
        'part_name' => 'Brake Pads',
        'brand' => 'Generic',
        'model' => 'Standard',
        'cost_price' => 500,
        'price' => 800,
        'quantity' => 1,
        'minimum_stock' => 5,
        'low_stock_threshold' => 5,
        'low_stock_notified' => false,
    ], $overrides));
}

test('owner dashboard exposes low-stock counts and a restock list', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    lowStockItem(['part_name' => 'Critical Pad', 'quantity' => 0]);
    lowStockItem(['part_name' => 'Low Pad', 'quantity' => 2]);
    lowStockItem(['part_name' => 'Healthy Pad', 'quantity' => 50]);
    lowStockItem(['part_name' => 'Zero Pad', 'quantity' => 0]);

    $response = $this->actingAs($owner)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertViewHas('stats', fn ($stats) => $stats['lowStockCount'] === 3);
    $response->assertViewHas('outOfStockCount', 2);
    $response->assertViewHas('lowStockItems', fn ($items) => $items->count() === 3);
    $response->assertSee('Low Stock Alerts');
    $response->assertSee('Critical Pad');
});

test('the low-stock page lists only items at or below threshold and links back to the dashboard', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    lowStockItem(['part_name' => 'Restock Me', 'quantity' => 2]);
    lowStockItem(['part_name' => 'Plenty Left', 'quantity' => 99]);

    $response = $this->actingAs($owner)->get(route('low-stock.index'));

    $response->assertStatus(200);
    $response->assertSee('Restock Me');
    $response->assertDontSee('Plenty Left');
    $response->assertSee(route('dashboard', ['tab' => 'inventory']));
});

test('crossing the threshold records exactly one owner notification', function () {
    Notification::fake();

    $owner = User::factory()->create(['role' => 'owner']);
    $item = lowStockItem();

    SmsService::checkLowStockAlert($item);

    Notification::assertSentTo(
        $owner,
        StatusUpdated::class,
        fn ($notification) => $notification->title === 'Low Stock Alert'
            && str_contains($notification->link, 'low-stock')
            && str_contains($notification->link, 'highlight=' . $item->id)
    );
});

test('an already-notified item does not send a duplicate alert', function () {
    Notification::fake();

    $owner = User::factory()->create(['role' => 'owner']);
    $item = lowStockItem();
    $item->low_stock_notified = true;
    $item->save();

    SmsService::checkLowStockAlert($item->fresh());

    Notification::assertNothingSent();
});

test('a healthy item never triggers a low-stock notification', function () {
    Notification::fake();

    User::factory()->create(['role' => 'owner']);
    $item = lowStockItem(['quantity' => 40]);

    SmsService::checkLowStockAlert($item);

    Notification::assertNothingSent();
});

test('a gateway failure still records the dashboard notification', function () {
    Notification::fake();

    $owner = User::factory()->create(['role' => 'owner']);
    $item = lowStockItem();

    // Force the SMS gateway to report failure; the operator-facing dashboard
    // signal must be independent of external delivery.
    $gateway = Mockery::mock(\App\Services\SmsGatewayInterface::class);
    $gateway->shouldReceive('send')->andReturnFalse();

    SmsService::checkLowStockAlert($item, $gateway);

    Notification::assertSentTo($owner, StatusUpdated::class);
    expect($item->fresh()->low_stock_notified)->toBeFalse();
});
