<?php

use App\Models\InventoryItem;
use App\Jobs\CheckLowStockAlertJob;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Clear all credentials to guarantee Mock Mode is triggered
    config([
        'services.twilio.sid' => null,
        'services.twilio.auth_token' => null,
        'services.twilio.number' => null,
        'services.semaphore.api_key' => null,
        'services.semaphore.notify_number' => null,
        'services.movider.api_key' => null,
        'services.movider.api_secret' => null,
        'services.movider.notify_number' => null,
    ]);
});

test('CheckLowStockAlertJob dispatches SMS in mock mode successfully when session is present', function () {
    // Arrange: Create an item below threshold
    $item = InventoryItem::create([
        'category' => 'Brakes',
        'part_name' => 'Brake Pads',
        'brand' => 'Generic',
        'model' => 'Standard',
        'cost_price' => 500,
        'price' => 800,
        'quantity' => 1, // Below threshold
        'minimum_stock' => 5, // Threshold is 5
        'low_stock_threshold' => 5,
        'low_stock_notified' => false,
    ]);

    // Ensure session is started/bound so session()->flash() works in this test
    session()->put('test', 'value');

    Log::shouldReceive('channel')->with('single')->andReturnSelf();
    Log::shouldReceive('info')->once()->with(Mockery::on(function ($message) {
        return str_contains($message, 'MOCK SMS SENT') && str_contains($message, 'Brake Pads');
    }));

    // Act: Dispatch the job synchronously
    CheckLowStockAlertJob::dispatchSync($item->id);

    // Assert: Database state updated to low_stock_notified = true
    $item->refresh();
    expect($item->low_stock_notified)->toBeTrue();
});

test('SmsService send does not crash when session is completely unbound or in a queue/CLI context', function () {
    // Arrange: Create an item below threshold
    $item = InventoryItem::create([
        'category' => 'Brakes',
        'part_name' => 'Brake Pads',
        'brand' => 'Generic',
        'model' => 'Standard',
        'cost_price' => 500,
        'price' => 800,
        'quantity' => 1, // Below threshold
        'minimum_stock' => 5, // Threshold is 5
        'low_stock_threshold' => 5,
        'low_stock_notified' => false,
    ]);

    // Unbind session from Laravel container to simulate pure queue context
    app()->forgetInstance('session');
    app()->forgetInstance('session.store');

    // Act & Assert: This should run without throwing a RuntimeException
    expect(function () use ($item) {
        CheckLowStockAlertJob::dispatchSync($item->id);
    })->not->toThrow(RuntimeException::class);
});
