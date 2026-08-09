<?php

use App\Models\InventoryItem;
use App\Services\SmsGatewayInterface;
use App\Services\Sms\MockSmsGateway;
use App\Services\Sms\TwilioSmsGateway;
use App\Services\Sms\MoviderSmsGateway;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Clear all credentials
    config([
        'services.twilio.sid' => null,
        'services.twilio.auth_token' => null,
        'services.twilio.number' => null,
        'services.movider.api_key' => null,
        'services.movider.api_secret' => null,
        'services.movider.notify_number' => null,
    ]);
});

test('it resolves active gateway to MockSmsGateway by default', function () {
    $gateway = app(SmsGatewayInterface::class);
    expect($gateway)->toBeInstanceOf(MockSmsGateway::class);
});

test('it resolves active gateway to TwilioSmsGateway when Twilio credentials exist', function () {
    config([
        'services.twilio.sid' => 'AC123',
        'services.twilio.auth_token' => 'token123',
        'services.twilio.number' => '+12345',
    ]);
    
    $gateway = app(SmsGatewayInterface::class);
    expect($gateway)->toBeInstanceOf(TwilioSmsGateway::class);
});

test('it resolves active gateway to MoviderSmsGateway when Movider credentials exist', function () {
    config([
        'services.movider.api_key' => 'movKey123',
        'services.movider.api_secret' => 'movSecret123',
        'services.movider.notify_number' => '09171234567',
    ]);
    
    $gateway = app(SmsGatewayInterface::class);
    expect($gateway)->toBeInstanceOf(MoviderSmsGateway::class);
});

test('MockSmsGateway logs SMS statelessly without touching the session store', function () {
    $gateway = new MockSmsGateway();

    // Verify session store is not present/flashed
    session()->forget('mock_sms');

    Log::shouldReceive('channel')->with('single')->andReturnSelf();
    Log::shouldReceive('info')->once()->with(Mockery::on(function ($message) {
        return str_contains($message, '[MOCK SMS SENT to 09171234567]') && str_contains($message, 'Test message');
    }));

    $result = $gateway->send('09171234567', 'Test message');

    expect($result)->toBeTrue();
    expect(session()->has('mock_sms'))->toBeFalse(); // Must be stateless
});

test('SmsService checkLowStockAlert triggers send using the resolved gateway', function () {
    $item = InventoryItem::create([
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
    ]);

    // Create a mock SMS gateway that records the calls
    $mockGateway = Mockery::mock(SmsGatewayInterface::class);
    $mockGateway->shouldReceive('send')->once()->andReturn(true);

    // Call checkLowStockAlert using our mock gateway
    SmsService::checkLowStockAlert($item, $mockGateway);

    $item->refresh();
    expect($item->low_stock_notified)->toBeTrue();
});
