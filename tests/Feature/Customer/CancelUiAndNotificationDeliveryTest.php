<?php

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\PreOrder;
use App\Models\Sale;
use App\Models\User;
use App\Notifications\StatusUpdated;
use App\Services\NotificationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function uiCustomer(): array
{
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email]);

    return [$user, $customer];
}

function uiItem(string $name, int $qty = 10): InventoryItem
{
    return InventoryItem::create(['part_name' => $name, 'sku' => strtoupper($name), 'price' => 500, 'quantity' => $qty]);
}

function uiReservation(Customer $c, InventoryItem $item, int $qty, string $ref): Sale
{
    return Sale::create([
        'inventory_item_id' => $item->id, 'customer_id' => $c->id, 'quantity' => $qty,
        'price' => 500, 'total_price' => 500 * $qty, 'transaction_reference' => $ref,
        'status' => 'pending', 'reserved_at' => now(), 'expires_at' => now()->addHours(24),
    ]);
}

// ── Cancel button markup (regression: button disabled itself before the form could submit) ──

test('cancel buttons no longer disable themselves on click before the form submits', function () {
    [$user, $customer] = uiCustomer();
    uiReservation($customer, uiItem('Disc'), 1, 'TXN-UI-1');
    PreOrder::create([
        'customer_name' => $user->name, 'customer_id' => $customer->id, 'part_name' => 'Fairing',
        'model' => 'TMX', 'quantity' => 1, 'status' => 'pending', 'price' => 100, 'total' => 100,
        'is_paid' => false, 'is_stocked' => false,
    ]);

    $html = $this->actingAs($user)->withSession(['otp_verified' => true])
        ->get(route('customer.dashboard'))->assertOk()->getContent();

    // The defect: @click flipped `cancelling` (disabling the button) before the browser submitted.
    expect($html)->not->toContain('@click="cancelling = true"');
    // The loading state is now entered only after the confirm dialog is accepted.
    expect(substr_count($html, 'cancelling = true; setTimeout'))->toBe(2);
    expect(substr_count($html, '@pageshow.window="cancelling = false"'))->toBe(2);
    expect($html)->toContain('Cancel Reservation')->toContain('Cancel Pre-order');
});

test('part names with quotes cannot break the confirm dialog script', function () {
    [$user, $customer] = uiCustomer();
    PreOrder::create([
        'customer_name' => $user->name, 'customer_id' => $customer->id, 'part_name' => "Rider's \"Special\" Fairing",
        'model' => 'TMX', 'quantity' => 1, 'status' => 'pending', 'price' => 100, 'total' => 100,
        'is_paid' => false, 'is_stocked' => false,
    ]);

    $html = $this->actingAs($user)->withSession(['otp_verified' => true])
        ->get(route('customer.dashboard'))->assertOk()->getContent();

    expect($html)->not->toContain("confirm('Cancel this pre-order for Rider's");
});

// ── In-app notifications do not depend on a queue worker ──

test('StatusUpdated is delivered synchronously, not queued', function () {
    expect(new StatusUpdated('t', 'm') instanceof ShouldQueue)->toBeFalse();
});

test('reservation cancellation records customer and owner notifications even with a database queue and no worker', function () {
    config(['queue.default' => 'database']);

    [$user, $customer] = uiCustomer();
    $owner = User::factory()->create(['role' => 'owner']);
    $item = uiItem('Disc', 8);
    uiReservation($customer, $item, 2, 'TXN-NQ-1');

    $this->actingAs($user)->withSession(['otp_verified' => true])
        ->post(route('customer.reservation.cancel', 'TXN-NQ-1'))->assertSessionHas('success');

    expect($user->notifications()->count())->toBe(1);
    expect($user->notifications()->first()->data['title'])->toBe('Reservation Cancelled');
    expect($owner->notifications()->count())->toBe(1);
    expect($owner->notifications()->first()->data['link'])->toContain('/dashboard');
    expect($item->fresh()->quantity)->toBe(10);
});

test('pre-order cancellation records customer and owner notifications with no worker', function () {
    config(['queue.default' => 'database']);

    [$user, $customer] = uiCustomer();
    $owner = User::factory()->create(['role' => 'owner']);
    $po = PreOrder::create([
        'customer_name' => $user->name, 'customer_id' => $customer->id, 'part_name' => 'Fairing',
        'model' => 'TMX', 'quantity' => 1, 'status' => 'pending', 'price' => 100, 'total' => 100,
        'is_paid' => false, 'is_stocked' => false,
    ]);

    $this->actingAs($user)->withSession(['otp_verified' => true])
        ->post(route('customer.preorder.cancel', $po->id))->assertSessionHas('success');

    expect($po->fresh()->status)->toBe('cancelled');
    expect($user->notifications()->count())->toBe(1);
    expect($owner->notifications()->count())->toBe(1);
});

test('read and unread state of a stored notification behaves', function () {
    [$user, $customer] = uiCustomer();
    uiReservation($customer, uiItem('Disc'), 1, 'TXN-RD-1');
    $this->actingAs($user)->withSession(['otp_verified' => true])->post(route('customer.reservation.cancel', 'TXN-RD-1'));

    expect($user->unreadNotifications()->count())->toBe(1);
    $id = $user->notifications()->first()->id;
    $this->actingAs($user)->withSession(['otp_verified' => true])->post(route('notifications.read', $id));
    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

// ── Delivery failures never undo committed work ──

class ExplodingBroadcastNotification extends StatusUpdated
{
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toBroadcast(object $notifiable): \Illuminate\Notifications\Messages\BroadcastMessage
    {
        throw new RuntimeException('Reverb is down');
    }
}

test('a realtime broadcast failure keeps the in-app record and counts as recorded', function () {
    $user = User::factory()->create();

    $recorded = app(NotificationDispatcher::class)->send($user, new ExplodingBroadcastNotification('T', 'M'));

    expect($recorded)->toBe(1);
    expect($user->notifications()->count())->toBe(1);
});

test('one recipient failing does not stop other recipients being notified', function () {
    $good = User::factory()->create();
    $bad = Mockery::mock(User::class)->makePartial();
    $bad->id = 999;
    $bad->shouldReceive('notifyNow')->andThrow(new RuntimeException('db down'));

    $recorded = app(NotificationDispatcher::class)->send([$bad, $good], new StatusUpdated('T', 'M'));

    expect($recorded)->toBe(1);
    expect($good->notifications()->count())->toBe(1);
});

test('a notification outage cannot undo a committed reservation cancellation', function () {
    app()->instance(NotificationDispatcher::class, new class extends NotificationDispatcher {
        public function send($recipients, StatusUpdated $template): int
        {
            throw new RuntimeException('SMTP/DB notification outage');
        }
    });

    [$user, $customer] = uiCustomer();
    $item = uiItem('Disc', 8);
    $line = uiReservation($customer, $item, 2, 'TXN-FAIL-1');

    $this->actingAs($user)->withSession(['otp_verified' => true])
        ->post(route('customer.reservation.cancel', 'TXN-FAIL-1'))->assertSessionHas('success');

    expect($line->fresh()->status)->toBe('cancelled');
    expect($item->fresh()->quantity)->toBe(10);
});

test('a notification outage cannot undo a committed pre-order cancellation', function () {
    app()->instance(NotificationDispatcher::class, new class extends NotificationDispatcher {
        public function send($recipients, StatusUpdated $template): int
        {
            throw new RuntimeException('outage');
        }
    });

    [$user, $customer] = uiCustomer();
    $po = PreOrder::create([
        'customer_name' => $user->name, 'customer_id' => $customer->id, 'part_name' => 'Fairing',
        'model' => 'TMX', 'quantity' => 1, 'status' => 'pending', 'price' => 100, 'total' => 100,
        'is_paid' => false, 'is_stocked' => false,
    ]);

    $this->actingAs($user)->withSession(['otp_verified' => true])
        ->post(route('customer.preorder.cancel', $po->id))->assertSessionHas('success');

    expect($po->fresh()->status)->toBe('cancelled');
});

test('pickup deadline does NOT start when the readiness notification could not be recorded', function () {
    app()->instance(NotificationDispatcher::class, new class extends NotificationDispatcher {
        public function send($recipients, StatusUpdated $template): int
        {
            return 0;
        }
    });

    [$user, $customer] = uiCustomer();
    $owner = User::factory()->create(['role' => 'owner']);
    $item = uiItem('Fairing', 5);
    $po = PreOrder::create([
        'customer_name' => $user->name, 'customer_id' => $customer->id, 'inventory_item_id' => $item->id,
        'part_name' => 'Fairing', 'model' => 'TMX', 'quantity' => 1, 'status' => 'pending',
        'price' => 100, 'total' => 100, 'is_paid' => false, 'is_stocked' => false,
    ]);

    $this->actingAs($owner)->withSession(['otp_verified' => true])->post(route('preorders.stock', $po));

    $fresh = $po->fresh();
    expect($fresh->ready_notified_at)->toBeNull();
    expect($fresh->pickup_expires_at)->toBeNull();
});

test('pickup deadline starts once the readiness notification row exists', function () {
    [$user, $customer] = uiCustomer();
    $owner = User::factory()->create(['role' => 'owner']);
    $item = uiItem('Fairing', 5);
    $po = PreOrder::create([
        'customer_name' => $user->name, 'customer_id' => $customer->id, 'inventory_item_id' => $item->id,
        'part_name' => 'Fairing', 'model' => 'TMX', 'quantity' => 1, 'status' => 'pending',
        'price' => 100, 'total' => 100, 'is_paid' => false, 'is_stocked' => false,
    ]);

    $this->actingAs($owner)->withSession(['otp_verified' => true])->post(route('preorders.stock', $po));

    $fresh = $po->fresh();
    expect($fresh->ready_notified_at)->not->toBeNull();
    expect($fresh->pickup_expires_at)->not->toBeNull();
    expect($user->notifications()->where('data->title', 'Pre-order Arrived — Ready for Pickup')->count())->toBe(1);
});

test('owners() survives a real serializing cache store (regression: __PHP_Incomplete_Class)', function () {
    $dir = sys_get_temp_dir() . '/mototrack-cache-' . uniqid();
    mkdir($dir);
    config(['cache.default' => 'file', 'cache.stores.file.path' => $dir]);
    app('cache')->forgetDriver('file');
    \Illuminate\Support\Facades\Cache::flush();

    $owner = User::factory()->create(['role' => 'owner']);
    User::factory()->create(['role' => 'customer']);

    $first = User::owners();
    $second = User::owners(); // served from the file cache

    expect($second)->toHaveCount(1);
    expect($second->first())->toBeInstanceOf(User::class);
    expect($second->first()->id)->toBe($owner->id);

    // and notifying them works end to end through the cache-backed path
    expect(app(NotificationDispatcher::class)->send($second, new StatusUpdated('T', 'M')))->toBe(1);
    expect($owner->notifications()->count())->toBe(1);
});

test('a demoted owner is not notified from a stale cached list', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    User::owners();                       // warm the cache
    $owner->forceFill(['role' => 'customer'])->save();

    expect(User::owners())->toHaveCount(0);
});

test('cancellation notices never end with a doubled period, whether the reason has one or not', function () {
    [$user, $customer] = uiCustomer();
    User::factory()->create(['role' => 'owner']);
    uiReservation($customer, uiItem('Disc'), 1, 'TXN-DOT-1');
    uiReservation($customer, uiItem('Mirror'), 1, 'TXN-DOT-2');

    $this->actingAs($user)->withSession(['otp_verified' => true]);
    $this->post(route('customer.reservation.cancel', 'TXN-DOT-1'));                       // default reason ends with "."
    $this->post(route('customer.reservation.cancel', 'TXN-DOT-2'), ['reason' => 'Too slow']); // no trailing period

    $messages = $user->notifications()->get()->pluck('data.message')->all();

    expect($messages)->toHaveCount(2);
    foreach ($messages as $m) {
        expect($m)->not->toContain('..')->and($m)->toEndWith('.');
    }
    expect(collect($messages)->contains(fn ($m) => str_contains($m, 'Reason: Too slow.')))->toBeTrue();
    // the stored cancellation reason itself is untouched
    expect(Sale::where('transaction_reference', 'TXN-DOT-1')->first()->cancellation_reason)->toBe('Customer changed their mind.');
});
