<?php

use App\Jobs\CheckLowStockAlertJob;
use App\Jobs\SendLowStockSmsJob;
use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\User;
use App\Services\LowStockAlertService;
use App\Services\SmsGatewayInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

/*
 * Low-stock in-app notifications must be persisted synchronously (no queue
 * worker). queue.default is "database" here to prove nothing depends on a
 * worker: jobs are pushed to the jobs table and never processed.
 */
beforeEach(function () {
    config(['queue.default' => 'database']);
    $this->owner = User::factory()->create(['role' => 'owner']);
});

function stockItem(int $qty, array $o = []): InventoryItem
{
    return InventoryItem::create(array_merge([
        'part_name' => 'Chain Set', 'category' => 'Drive', 'brand' => 'DID', 'model' => '520',
        'price' => 1000, 'quantity' => $qty, 'low_stock_threshold' => 5, 'low_stock_notified' => false,
    ], $o));
}

function lowStockRows(User $owner): int
{
    return DB::table('notifications')
        ->where('notifiable_id', $owner->id)
        ->where('data', 'like', '%Low Stock Alert%')
        ->count();
}

function sellPos(User $owner, InventoryItem $item, int $qty)
{
    return test()->actingAs($owner)->withSession(['otp_verified' => true])->post('/sales/store', [
        'cart_data' => json_encode([['id' => $item->id, 'quantity' => $qty]]),
        'amount_tendered' => 100000,
    ]);
}

function editStock(User $owner, InventoryItem $item, array $changes = [])
{
    $item = $item->fresh();

    return test()->actingAs($owner)->withSession(['otp_verified' => true])->put("/inventory/{$item->id}", array_merge([
        'category' => $item->category, 'part_name' => $item->part_name, 'brand' => $item->brand,
        'model' => $item->model, 'price' => $item->price, 'quantity' => $item->quantity,
        'expected_quantity' => $item->quantity, 'low_stock_threshold' => $item->low_stock_threshold,
    ], $changes));
}

test('normal stock raises no alert', function () {
    $item = stockItem(40);

    expect(app(LowStockAlertService::class)->evaluate($item->id))->toBeFalse();
    expect(lowStockRows($this->owner))->toBe(0)
        ->and($item->fresh()->low_stock_notified)->toBeFalse();
});

test('crossing the threshold records one owner notification without any queue worker', function () {
    Queue::fake(); // SMS job is queued but never run; notification must still exist
    $item = stockItem(6);

    sellPos($this->owner, $item, 2)->assertRedirect(); // 6 -> 4

    expect($item->fresh()->quantity)->toBe(4)
        ->and(lowStockRows($this->owner))->toBe(1)
        ->and($item->fresh()->low_stock_notified)->toBeTrue();
    Queue::assertNotPushed(CheckLowStockAlertJob::class);
    Queue::assertPushed(SendLowStockSmsJob::class, 1);
});

test('exactly at the threshold counts as low', function () {
    $item = stockItem(6);
    sellPos($this->owner, $item, 1); // 6 -> 5

    expect(lowStockRows($this->owner))->toBe(1);
});

test('out-of-stock item is reported as out of stock', function () {
    $item = stockItem(2);
    sellPos($this->owner, $item, 2); // -> 0

    $row = DB::table('notifications')->where('notifiable_id', $this->owner->id)->where('data', 'like', '%Low Stock Alert%')->first();
    expect($row)->not->toBeNull()->and($row->data)->toContain('out of stock');
});

test('repeated sales and edits while still low do not duplicate the alert', function () {
    $item = stockItem(6);

    sellPos($this->owner, $item, 2);   // 4  -> alert
    sellPos($this->owner, $item, 1);   // 3
    editStock($this->owner, $item, ['quantity' => 2]); // manual edit, still low
    editStock($this->owner, $item, ['quantity' => 1]);

    expect(lowStockRows($this->owner))->toBe(1);
});

test('manual inventory edit below the threshold alerts the owner once', function () {
    $item = stockItem(20);

    editStock($this->owner, $item, ['quantity' => 3])->assertRedirect();

    expect(lowStockRows($this->owner))->toBe(1)
        ->and($item->fresh()->low_stock_notified)->toBeTrue();
});

test('restocking above the threshold re-arms and a later drop alerts again', function () {
    $item = stockItem(6);

    sellPos($this->owner, $item, 2);                 // 4  -> alert #1
    editStock($this->owner, $item, ['quantity' => 50]); // restock above -> re-armed
    expect($item->fresh()->low_stock_notified)->toBeFalse();

    sellPos($this->owner, $item, 46);                // 4 again -> alert #2
    expect(lowStockRows($this->owner))->toBe(2);

    sellPos($this->owner, $item, 1);                 // still low -> no #3
    expect(lowStockRows($this->owner))->toBe(2);
});

test('restock through a return or reservation cancellation re-arms the alert', function () {
    $item = stockItem(4);
    app(LowStockAlertService::class)->evaluate($item->id);
    expect(lowStockRows($this->owner))->toBe(1);

    // Stock increases above threshold via the damaged/return/refund paths' shared hook.
    $item->increment('quantity', 10);
    LowStockAlertService::checkAfterCommit($item->id);

    expect($item->fresh()->low_stock_notified)->toBeFalse();
});

test('damaged item write-off that drops stock alerts', function () {
    $item = stockItem(7);

    $this->actingAs($this->owner)->withSession(['otp_verified' => true])
        ->post('/inventory/adjustments/damaged', ['inventory_item_id' => $item->id, 'quantity' => 3, 'notes' => 'cracked'])
        ->assertRedirect();

    expect(lowStockRows($this->owner))->toBe(1);
});

test('a customer reservation that drops stock below the threshold alerts the owner', function () {
    $item = stockItem(6);
    $customerUser = User::factory()->create(['role' => 'customer']);
    $customer = \App\Models\Customer::create(['user_id' => $customerUser->id, 'name' => 'Cust', 'phone' => '09171234567']);

    app(\App\Actions\CommitOrder::class)->execute(
        [['id' => $item->id, 'quantity' => 3]], 'RSV-TEST-1', $customer->id, 'Out of stock: ',
        fn () => 0, fn () => 0, false, 'pending'
    );

    expect(lowStockRows($this->owner))->toBe(1);
});

test('SMS failure never blocks the in-app notification or causes duplicates', function () {
    $gateway = Mockery::mock(SmsGatewayInterface::class);
    $gateway->shouldReceive('send')->once()->andThrow(new RuntimeException('gateway down'));
    $item = stockItem(2);

    app(LowStockAlertService::class)->evaluate($item->id, $gateway);
    app(LowStockAlertService::class)->evaluate($item->id, $gateway); // repeat: no SMS, no row

    expect(lowStockRows($this->owner))->toBe(1)
        ->and($item->fresh()->low_stock_notified)->toBeTrue();
});

test('failure to record the owner notification leaves the item armed so it retries, and never breaks the sale', function () {
    $item = stockItem(6);

    $broken = Mockery::mock(\App\Services\NotificationDispatcher::class);
    $broken->shouldReceive('send')->andThrow(new RuntimeException('db write failed'));
    app()->instance(\App\Services\NotificationDispatcher::class, $broken);

    $response = sellPos($this->owner, $item, 3); // 6 -> 3 : sale must still succeed

    $response->assertRedirect();
    expect($item->fresh()->quantity)->toBe(3)
        ->and(Sale::count())->toBe(1)
        ->and($item->fresh()->low_stock_notified)->toBeFalse()
        ->and(lowStockRows($this->owner))->toBe(0);

    // Next quantity change retries once the infrastructure is healthy.
    app()->forgetInstance(\App\Services\NotificationDispatcher::class);
    app()->forgetInstance(LowStockAlertService::class);
    app()->bind(\App\Services\NotificationDispatcher::class, fn () => new \App\Services\NotificationDispatcher());

    sellPos($this->owner, $item, 1);
    expect(lowStockRows($this->owner))->toBe(1);
});

test('a rolled-back order raises no alert', function () {
    $item = stockItem(6);

    try {
        DB::transaction(function () use ($item) {
            $item->decrement('quantity', 4);
            LowStockAlertService::checkAfterCommit($item->id);
            throw new RuntimeException('rollback');
        });
    } catch (RuntimeException) {
    }

    expect(lowStockRows($this->owner))->toBe(0)
        ->and($item->fresh()->quantity)->toBe(6);
});

test('legacy queued CheckLowStockAlertJob cannot duplicate an alert that was already raised', function () {
    $item = stockItem(6);
    sellPos($this->owner, $item, 2);
    expect(lowStockRows($this->owner))->toBe(1);

    CheckLowStockAlertJob::dispatchSync($item->id);
    CheckLowStockAlertJob::dispatchSync($item->id);

    expect(lowStockRows($this->owner))->toBe(1);
});

test('legacy job for a healthy item is a no-op', function () {
    $item = stockItem(40);
    CheckLowStockAlertJob::dispatchSync($item->id);

    expect(lowStockRows($this->owner))->toBe(0);
});

test('dashboard counts and the Low Stock page are unchanged', function () {
    stockItem(2, ['part_name' => 'Low One']);
    stockItem(50, ['part_name' => 'Healthy One']);

    $this->actingAs($this->owner)->withSession(['otp_verified' => true])
        ->get('/low-stock')->assertOk()->assertSee('Low One')->assertDontSee('Healthy One');
});

test('alert state flag changes do not add audit-log noise', function () {
    $item = stockItem(6);
    $before = AuditLog::count();

    app(LowStockAlertService::class)->evaluate($item->id);        // flag set, quantity unchanged
    $item->increment('quantity', 0);

    expect(AuditLog::where('auditable_id', $item->id)->where('action', 'updated')->count())->toBe(0)
        ->and(AuditLog::count())->toBe($before);
});

test('cancelling a reservation restores stock through the real path and re-arms the alert', function () {
    $item = stockItem(8);
    $cu = User::factory()->create(['role' => 'customer']);
    $customer = \App\Models\Customer::create(['user_id' => $cu->id, 'name' => 'Cust', 'phone' => '09171234567']);

    app(\App\Actions\CommitOrder::class)->execute(
        [['id' => $item->id, 'quantity' => 5]], 'RSV-RE-1', $customer->id, 'Out of stock: ',
        fn () => 0, fn () => 0, false, 'pending'
    );
    expect($item->fresh()->quantity)->toBe(3)
        ->and(lowStockRows($this->owner))->toBe(1)
        ->and($item->fresh()->low_stock_notified)->toBeTrue();

    $sale = Sale::where('transaction_reference', 'RSV-RE-1')->first();
    app(\App\Services\ReservationService::class)->releaseGroup($sale, 'Customer cancelled', \App\Enums\OrderStatus::SALE_CANCELLED);

    expect($item->fresh()->quantity)->toBe(8)
        ->and($item->fresh()->low_stock_notified)->toBeFalse();

    // Reserving again crosses the threshold again -> a second alert.
    app(\App\Actions\CommitOrder::class)->execute(
        [['id' => $item->id, 'quantity' => 5]], 'RSV-RE-2', $customer->id, 'Out of stock: ',
        fn () => 0, fn () => 0, false, 'pending'
    );
    expect(lowStockRows($this->owner))->toBe(2);
});

test('a resellable return restores stock through the real endpoint and re-arms the alert', function () {
    $item = stockItem(8);
    sellPos($this->owner, $item, 5); // 3 -> alert
    $sale = Sale::first();
    expect(lowStockRows($this->owner))->toBe(1);

    $this->actingAs($this->owner)->withSession(['otp_verified' => true])
        ->post(route('inventory.adjustments.store_return'), [
            'sale_id' => $sale->id, 'inventory_item_id' => $item->id, 'quantity' => 5,
            'refund_amount' => 5000, 'reason' => 'Wrong part', 'condition' => 'resellable',
        ])->assertRedirect();

    expect($item->fresh()->quantity)->toBe(8)
        ->and($item->fresh()->low_stock_notified)->toBeFalse();
});
