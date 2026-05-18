<?php

use App\Models\PreOrder;
use App\Models\Customer;
use App\Models\User;
use App\Models\Sale;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\Notification;
use App\Notifications\StatusUpdated;

test('it rejects webhook requests with invalid signature', function () {
    $response = $this->postJson('/api/rcpay-webhook', [
        'order_id' => 'PRE-1-12345678',
        'amount' => '100.00',
        'status' => 'SUCCESS',
        'signature' => 'invalid-signature'
    ]);

    $response->assertStatus(400);
});

test('it ignores webhook requests with status other than SUCCESS', function () {
    // Generate valid signature
    $payload = [
        'order_id' => 'PRE-1-12345678',
        'amount' => '100.00',
        'status' => 'PENDING',
    ];
    
    ksort($payload);
    $signStr = "";
    foreach ($payload as $key => $value) {
        $signStr .= $key . '=' . $value . '&';
    }
    $signStr .= 'key=' . config('services.rcpay.api_key');
    $payload['signature'] = md5($signStr);

    $response = $this->postJson('/api/rcpay-webhook', $payload);

    $response->assertStatus(200);
    $response->assertJsonFragment(['message' => 'Ignored: Status is not SUCCESS']);
});

test('it confirms pre-order down payment successfully', function () {
    Notification::fake();

    $owner = User::factory()->create(['role' => 'owner']);
    $user = User::factory()->create();
    $customer = Customer::create([
        'user_id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
    ]);

    $preorder = PreOrder::create([
        'customer_id' => $customer->id,
        'customer_name' => $user->name,
        'part_name' => 'Brake Pad',
        'quantity' => 1,
        'down_payment' => 500.00,
        'status' => 'pending',
        'is_paid' => false,
        'price' => 1000.00,
        'total' => 1000.00,
    ]);

    $orderId = 'PRE-' . $preorder->id . '-' . time();

    // Generate valid signature
    $payload = [
        'order_id' => $orderId,
        'amount' => '500.00',
        'status' => 'SUCCESS',
        'payment_method' => 'PMP',
        'bank_code' => 'PMP',
        'transaction_id' => 'SYNC123456',
        'timestamp' => '2026-05-18 17:00:00',
    ];
    
    ksort($payload);
    $signStr = "";
    foreach ($payload as $key => $value) {
        $signStr .= $key . '=' . $value . '&';
    }
    $signStr .= 'key=' . config('services.rcpay.api_key');
    $payload['signature'] = md5($signStr);

    $response = $this->postJson('/api/rcpay-webhook', $payload);

    $response->assertStatus(200);
    
    // Verify pre-order is updated
    $preorder->refresh();
    expect($preorder->is_paid)->toBeTrue();

    // Verify notifications were sent
    Notification::assertSentTo(
        $owner,
        StatusUpdated::class,
        function ($notification) use ($owner) {
            $data = $notification->toArray($owner);
            return str_contains($data['title'], 'Pre-order Payment Received');
        }
    );

    Notification::assertSentTo(
        $user,
        StatusUpdated::class,
        function ($notification) use ($user) {
            $data = $notification->toArray($user);
            return str_contains($data['title'], 'Pre-order Down Payment Confirmed');
        }
    );
});

test('it confirms shop order payment successfully', function () {
    Notification::fake();

    $owner = User::factory()->create(['role' => 'owner']);
    $user = User::factory()->create();
    $customer = Customer::create([
        'user_id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
    ]);

    $item = InventoryItem::create([
        'part_name' => 'Brake Pad',
        'price' => 1000.00,
        'quantity' => 10,
    ]);

    $transactionReference = 'TRX-' . strtoupper(uniqid());

    Sale::create([
        'inventory_item_id' => $item->id,
        'customer_id' => $customer->id,
        'quantity' => 1,
        'price' => 1000.00,
        'total_price' => 1000.00,
        'transaction_reference' => $transactionReference,
    ]);

    // Generate valid signature
    $payload = [
        'order_id' => $transactionReference,
        'amount' => '1000.00',
        'status' => 'SUCCESS',
        'payment_method' => 'PMP',
        'bank_code' => 'PMP',
        'transaction_id' => 'SYNC789012',
        'timestamp' => '2026-05-18 17:00:00',
    ];
    
    ksort($payload);
    $signStr = "";
    foreach ($payload as $key => $value) {
        $signStr .= $key . '=' . $value . '&';
    }
    $signStr .= 'key=' . config('services.rcpay.api_key');
    $payload['signature'] = md5($signStr);

    $response = $this->postJson('/api/rcpay-webhook', $payload);

    $response->assertStatus(200);

    // Verify notifications were sent
    Notification::assertSentTo(
        $owner,
        StatusUpdated::class,
        function ($notification) use ($owner) {
            $data = $notification->toArray($owner);
            return str_contains($data['title'], 'Shop Order Payment Confirmed');
        }
    );

    Notification::assertSentTo(
        $user,
        StatusUpdated::class,
        function ($notification) use ($user) {
            $data = $notification->toArray($user);
            return str_contains($data['title'], 'Shop Order Payment Confirmed');
        }
    );
});
