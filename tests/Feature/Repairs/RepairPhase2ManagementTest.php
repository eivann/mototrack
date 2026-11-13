<?php

use App\Models\Customer;
use App\Models\Repair;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('F03: authorized owner can update repair details including vehicle, issue, customer, and unpaid cost', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $customer1 = Customer::create(['name' => 'Original Customer', 'phone' => '09171234567']);
    $customer2 = Customer::create(['name' => 'Updated Customer', 'phone' => '09187654321']);

    $repair = Repair::create([
        'customer_id'   => $customer1->id,
        'customer_name' => $customer1->name,
        'bike_model'    => 'Yamaha Mio Sporty',
        'issue'         => 'Brake fluid leak',
        'cost'          => 350.00,
        'status'        => 'pending',
        'is_paid'       => false,
    ]);

    $response = $this->actingAs($owner)->put(route('repairs.update', $repair), [
        'customer_id'   => $customer2->id,
        'bike_model'    => 'Yamaha NMAX 155',
        'issue'         => 'Replaced brake caliper and fluid flushed',
        'cost'          => 850.00,
        'status'        => 'pending',
    ]);

    $response->assertRedirect('/repairs');
    $response->assertSessionHas('success', 'Repair job updated successfully!');

    $repair->refresh();
    expect($repair->customer_id)->toBe($customer2->id)
        ->and($repair->customer_name)->toBe('Updated Customer')
        ->and($repair->bike_model)->toBe('Yamaha NMAX 155')
        ->and($repair->issue)->toBe('Replaced brake caliper and fluid flushed')
        ->and((float) $repair->cost)->toBe(850.00)
        ->and($repair->status)->toBe('pending');
});

test('F03: unauthorized users cannot update repair records', function () {
    $customerUser = User::factory()->create(['role' => 'customer']);
    $repair = Repair::create([
        'customer_name' => 'Private Job',
        'bike_model'    => 'Honda Beat',
        'issue'         => 'Throttle cable stuck',
        'cost'          => 200.00,
        'status'        => 'pending',
        'is_paid'       => false,
    ]);

    // Guest request
    $this->put(route('repairs.update', $repair), [
        'bike_model' => 'Hacked Model',
        'status'     => 'pending',
    ])->assertRedirect(route('login'));

    // Customer request
    $this->actingAs($customerUser)->put(route('repairs.update', $repair), [
        'bike_model' => 'Hacked Model',
        'status'     => 'pending',
    ])->assertForbidden();

    $repair->refresh();
    expect($repair->bike_model)->toBe('Honda Beat');
});

test('F03: validation rejects invalid inputs when editing repair', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $repair = Repair::create([
        'customer_name' => 'Validation Test',
        'bike_model'    => 'Kawasaki Barako',
        'issue'         => 'Clutch check',
        'cost'          => 300.00,
        'status'        => 'pending',
        'is_paid'       => false,
    ]);

    $response = $this->actingAs($owner)->put(route('repairs.update', $repair), [
        'status'        => 'invalid_status',
        'bike_model'    => '', // required
        'customer_id'   => 999999, // non-existent
        'cost'          => -50, // min 0
    ]);

    $response->assertSessionHasErrors(['status', 'bike_model', 'customer_id', 'cost']);

    $repair->refresh();
    expect($repair->bike_model)->toBe('Kawasaki Barako');
});

test('F03 & F06: financial lock prevents modifying or clearing cost of already paid repairs while permitting descriptive edits', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $repair = new Repair([
        'customer_name' => 'Paid Client',
        'bike_model'    => 'Honda Click 125',
        'issue'         => 'Wiring harness inspect',
        'cost'          => 750.00,
        'status'        => 'done',
        'is_paid'       => true,
    ]);
    $repair->paid_at = Carbon::now();
    $repair->save();

    // Attempting to change cost on a paid repair must be rejected
    $response = $this->actingAs($owner)->put(route('repairs.update', $repair), [
        'cost'       => 400.00, // Different cost
        'bike_model' => 'Honda Click 150',
        'status'     => 'done',
    ]);

    $response->assertRedirect('/repairs');
    $response->assertSessionHas('error', 'This repair is already paid, so its cost can no longer be changed.');

    $repair->refresh();
    expect((float) $repair->cost)->toBe(750.00)
        ->and($repair->bike_model)->toBe('Honda Click 125');

    // Attempting to clear cost on a paid repair must also be rejected
    $responseNull = $this->actingAs($owner)->put(route('repairs.update', $repair), [
        'cost'       => null,
        'bike_model' => 'Honda Click 150',
        'status'     => 'done',
    ]);

    $responseNull->assertRedirect('/repairs');
    $responseNull->assertSessionHas('error', 'This repair is already paid, so its cost can no longer be changed.');

    $repair->refresh();
    expect((float) $repair->cost)->toBe(750.00);

    // Updating description/issue without altering cost on paid repair succeeds
    $responseOk = $this->actingAs($owner)->put(route('repairs.update', $repair), [
        'cost'       => 750.00, // Identical cost preserved
        'bike_model' => 'Honda Click 150i',
        'issue'      => 'Wiring harness replaced completely',
        'status'     => 'done',
    ]);

    $responseOk->assertRedirect('/repairs');
    $responseOk->assertSessionHas('success');

    $repair->refresh();
    expect((float) $repair->cost)->toBe(750.00)
        ->and($repair->bike_model)->toBe('Honda Click 150i')
        ->and($repair->issue)->toBe('Wiring harness replaced completely');
});

test('F06: service tracking updates status and integrates with RepairCapacityService', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $repair = Repair::create([
        'customer_name' => 'Capacity Job',
        'bike_model'    => 'Suzuki Raider 150',
        'issue'         => 'Valve clearance',
        'cost'          => 500.00,
        'status'        => 'pending',
        'is_paid'       => false,
    ]);

    // Move to ongoing with 90 minute estimate
    $this->actingAs($owner)->put(route('repairs.update', $repair), [
        'status'            => 'ongoing',
        'estimated_minutes' => 90,
    ])->assertRedirect('/repairs');

    $repair->refresh();
    expect($repair->status)->toBe('ongoing')
        ->and($repair->started_at)->not->toBeNull()
        ->and($repair->estimated_minutes)->toBe(90)
        ->and($repair->completed_at)->toBeNull();

    // Move to done
    $this->actingAs($owner)->put(route('repairs.update', $repair), [
        'status' => 'done',
    ])->assertRedirect('/repairs');

    $repair->refresh();
    expect($repair->status)->toBe('done')
        ->and($repair->completed_at)->not->toBeNull();
});

test('F06: dedicated service tracking tab renders live tracking interface and is inaccessible to customers', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $customerUser = User::factory()->create(['role' => 'customer']);

    // Create an ongoing repair to test live floor tracking
    Repair::create([
        'customer_name'     => 'Live Floor Customer',
        'bike_model'        => 'Yamaha Sniper 155',
        'issue'             => 'Engine noise diagnosis',
        'cost'              => 600.00,
        'status'            => 'ongoing',
        'started_at'        => Carbon::now('Asia/Manila'),
        'estimated_minutes' => 60,
    ]);

    // Owner accesses the dedicated Service Tracking tab
    $response = $this->actingAs($owner)->get(route('repairs.index', ['tab' => 'tracking']));
    $response->assertOk();
    $html = $response->getContent();

    expect($html)->toContain('Service Tracking &amp; Bay Management')
        ->and($html)->toContain('Live Floor Customer')
        ->and($html)->toContain('Yamaha Sniper 155')
        ->and($html)->toContain('Edit Tracking Details');

    // Customer is forbidden
    $this->actingAs($customerUser)->get(route('repairs.index', ['tab' => 'tracking']))->assertForbidden();
});

test('F08: repair transaction timestamps format correctly in Asia/Manila', function () {
    $created   = Carbon::create(2026, 11, 13, 8, 30, 0, 'Asia/Manila');
    $started   = Carbon::create(2026, 11, 13, 9, 0, 0, 'Asia/Manila');
    $completed = Carbon::create(2026, 11, 13, 10, 15, 0, 'Asia/Manila');
    $paid      = Carbon::create(2026, 11, 13, 10, 30, 0, 'Asia/Manila');

    $repair = new Repair([
        'customer_name' => 'Timestamp Test',
        'bike_model'    => 'Yamaha Aerox',
        'issue'         => 'CVT cleaning',
        'cost'          => 450.00,
        'status'        => 'done',
        'is_paid'       => true,
        'started_at'    => $started,
        'completed_at'  => $completed,
    ]);
    $repair->paid_at = $paid;
    $repair->created_at = $created;
    $repair->save();

    expect($repair->createdAtManila())->toBe('Nov 13, 2026 8:30 AM')
        ->and($repair->startedAtManila())->toBe('Nov 13, 2026 9:00 AM')
        ->and($repair->completedAtManila())->toBe('Nov 13, 2026 10:15 AM')
        ->and($repair->paidAtManila())->toBe('Nov 13, 2026 10:30 AM');
});

test('F08: regression test - midnight (00:00:00) transaction is included in same-day filter', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $targetDate = '2026-11-13';

    // Transaction at exact midnight 00:00:00
    $r = new Repair([
        'customer_name' => 'Midnight Rider',
        'bike_model'    => 'Honda Beat',
        'issue'         => 'Flat tire midnight',
        'status'        => 'pending',
    ]);
    $r->created_at = Carbon::parse('2026-11-13 00:00:00', 'Asia/Manila');
    $r->save();

    $response = $this->actingAs($owner)->get("/repairs?tab=records&date_from={$targetDate}&date_to={$targetDate}");
    $response->assertOk();

    expect($response->getContent())->toContain('Midnight Rider');
});

test('F08: regression test - 23:59:59 with microseconds is included in same-day filter', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $targetDate = '2026-11-13';

    // Transaction at 23:59:59 with microseconds
    $r = new Repair([
        'customer_name' => 'Microsecond Rider',
        'bike_model'    => 'Suzuki Smash',
        'issue'         => 'Late night microsecond check',
        'status'        => 'pending',
    ]);
    $r->created_at = Carbon::parse('2026-11-13 23:59:59.999999', 'Asia/Manila');
    $r->save();

    $response = $this->actingAs($owner)->get("/repairs?tab=records&date_from={$targetDate}&date_to={$targetDate}");
    $response->assertOk();

    expect($response->getContent())->toContain('Microsecond Rider');
});

test('F08: regression test - same-day date range filter strictly includes matching day and excludes adjacent days', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $targetDate = '2026-11-13';

    // Prior day 23:59:59
    $prior = new Repair(['customer_name' => 'Prior Day Rider', 'bike_model' => 'Bike A', 'issue' => 'Issue A', 'status' => 'pending']);
    $prior->created_at = Carbon::parse('2026-11-12 23:59:59', 'Asia/Manila');
    $prior->save();

    // Target day early morning
    $today = new Repair(['customer_name' => 'Target Day Rider', 'bike_model' => 'Bike B', 'issue' => 'Issue B', 'status' => 'pending']);
    $today->created_at = Carbon::parse('2026-11-13 10:00:00', 'Asia/Manila');
    $today->save();

    // Next day midnight 00:00:00
    $next = new Repair(['customer_name' => 'Next Day Midnight Rider', 'bike_model' => 'Bike C', 'issue' => 'Issue C', 'status' => 'pending']);
    $next->created_at = Carbon::parse('2026-11-14 00:00:00', 'Asia/Manila');
    $next->save();

    $response = $this->actingAs($owner)->get("/repairs?tab=records&date_from={$targetDate}&date_to={$targetDate}");
    $response->assertOk();

    $html = $response->getContent();
    expect($html)->toContain('Target Day Rider')
        ->and($html)->not->toContain('Prior Day Rider')
        ->and($html)->not->toContain('Next Day Midnight Rider');
});

test('F08: regression test - reversed date range returns zero records cleanly without sql error', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $r = new Repair(['customer_name' => 'Existing Customer', 'bike_model' => 'Bike D', 'issue' => 'Issue D', 'status' => 'pending']);
    $r->created_at = Carbon::parse('2026-11-13 12:00:00', 'Asia/Manila');
    $r->save();

    // from: Nov 20, to: Nov 10 (reversed)
    $response = $this->actingAs($owner)->get('/repairs?tab=records&date_from=2026-11-20&date_to=2026-11-10');
    $response->assertOk();

    $html = $response->getContent();
    expect($html)->not->toContain('Existing Customer')
        ->and($html)->toContain('No repairs found.');
});

test('F08: regression test - null paid_at repairs are excluded when filtering by payment date', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    // Unpaid repair with null paid_at
    $unpaid = new Repair([
        'customer_name' => 'Unpaid Repair',
        'bike_model'    => 'Bike X',
        'issue'         => 'Issue X',
        'status'        => 'pending',
        'is_paid'       => false,
    ]);
    $unpaid->created_at = Carbon::parse('2026-11-13 10:00:00', 'Asia/Manila');
    $unpaid->save();

    // Paid repair with paid_at set today
    $paid = new Repair([
        'customer_name' => 'Paid Repair Today',
        'bike_model'    => 'Bike Y',
        'issue'         => 'Issue Y',
        'status'        => 'done',
        'cost'          => 500.00,
        'is_paid'       => true,
    ]);
    $paid->paid_at = Carbon::parse('2026-11-13 14:00:00', 'Asia/Manila');
    $paid->created_at = Carbon::parse('2026-11-10 10:00:00', 'Asia/Manila');
    $paid->save();

    // Filter by date_type=paid for 2026-11-13
    $response = $this->actingAs($owner)->get('/repairs?tab=records&date_type=paid&date_from=2026-11-13&date_to=2026-11-13');
    $response->assertOk();

    $html = $response->getContent();
    expect($html)->toContain('Paid Repair Today')
        ->and($html)->not->toContain('Unpaid Repair');
});

test('F08: regression test - timezone conversion handles Asia/Manila date boundaries properly', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    // Transaction at 7:00 AM Manila on 2026-11-13
    $r = new Repair(['customer_name' => 'Manila Morning Rider', 'bike_model' => 'Bike M', 'issue' => 'Issue M', 'status' => 'pending']);
    $r->created_at = Carbon::parse('2026-11-13 07:00:00', 'Asia/Manila');
    $r->save();

    $response = $this->actingAs($owner)->get('/repairs?tab=records&date_from=2026-11-13&date_to=2026-11-13');
    $response->assertOk();

    expect($response->getContent())->toContain('Manila Morning Rider');
});

test('F08: markPaid records paid_at timestamp without overwriting historical created_at or started_at', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $created = Carbon::create(2026, 11, 10, 8, 0, 0, 'Asia/Manila');
    $started = Carbon::create(2026, 11, 10, 9, 0, 0, 'Asia/Manila');

    $repair = new Repair([
        'customer_name' => 'Payment Timestamp Test',
        'bike_model'    => 'Kawasaki Ninja 400',
        'issue'         => 'Oil and filter change',
        'cost'          => 1200.00,
        'status'        => 'done',
        'is_paid'       => false,
        'started_at'    => $started,
    ]);
    $repair->created_at = $created;
    $repair->save();

    $this->actingAs($owner)->put("/repairs/{$repair->id}/pay", [
        'amount_tendered' => 1200.00,
    ])->assertRedirect();

    $repair->refresh();
    expect($repair->is_paid)->toBeTrue()
        ->and($repair->paid_at)->not->toBeNull()
        ->and($repair->created_at->format('Y-m-d H:i:s'))->toBe($created->format('Y-m-d H:i:s'))
        ->and($repair->started_at->format('Y-m-d H:i:s'))->toBe($started->format('Y-m-d H:i:s'));
});

test('F08: paid_at is not mass-assignable on create or update', function () {
    $fakeTimestamp = '2020-01-01 12:00:00';

    $repair = Repair::create([
        'customer_name' => 'Mass Assign Test',
        'bike_model'    => 'Yamaha Vega',
        'issue'         => 'Spark plug change',
        'cost'          => 150.00,
        'status'        => 'pending',
        'paid_at'       => $fakeTimestamp,
    ]);

    expect($repair->paid_at)->toBeNull();

    $repair->update([
        'paid_at' => $fakeTimestamp,
    ]);

    $repair->refresh();
    expect($repair->paid_at)->toBeNull();
});

test('F06: search query matches customer name, bike model, and issue description', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    Repair::create([
        'customer_name' => 'Alice Walker',
        'bike_model'    => 'Yamaha Sniper 155',
        'issue'         => 'ECU remap and dyno test',
        'status'        => 'ongoing',
    ]);

    Repair::create([
        'customer_name' => 'Bob Builder',
        'bike_model'    => 'Honda ADV 160',
        'issue'         => 'Belt replacement',
        'status'        => 'pending',
    ]);

    $response = $this->actingAs($owner)->get('/repairs?tab=records&search=Sniper');
    $response->assertOk();
    $html = $response->getContent();

    expect($html)->toContain('Alice Walker')
        ->and($html)->toContain('Yamaha Sniper 155')
        ->and($html)->not->toContain('Bob Builder');
});
