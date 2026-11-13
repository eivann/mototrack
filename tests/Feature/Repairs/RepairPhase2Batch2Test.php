<?php

use App\Models\Customer;
use App\Models\Repair;
use App\Models\SaleHeader;
use App\Models\User;
use App\Services\RepairReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function p2b2Owner(): User
{
    return User::query()->where('role', 'owner')->first() ?? User::factory()->create(['role' => 'owner']);
}

function p2b2CustomerUser(): array
{
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create([
        'user_id' => $user->id,
        'name'    => $user->name,
        'email'   => $user->email,
        'phone'   => '09171234567',
    ]);

    return [$user, $customer];
}

/* -------------------------------------------------------------------------
 | F01: Service Reports Authorization & Access Control
 * ------------------------------------------------------------------------- */

test('F01: authorized owner can view service reports page', function () {
    $owner = p2b2Owner();

    $response = $this->actingAs($owner)->get(route('repairs.reports'));

    $response->assertOk()
        ->assertSee('Service &amp; Repair Reports', false)
        ->assertSee('Total Services')
        ->assertSee('Turnaround')
        ->assertSee('Export CSV')
        ->assertSee('Export PDF');
});

test('F01: unauthenticated guests and customer accounts cannot access service reports', function () {
    [$customerUser] = p2b2CustomerUser();

    // Guest redirect to login
    $this->get(route('repairs.reports'))->assertRedirect(route('login'));

    // Customer forbidden (403)
    $this->actingAs($customerUser)->get(route('repairs.reports'))->assertForbidden();
});

/* -------------------------------------------------------------------------
 | F01: Turnaround Time, Invoiced Charges vs Recorded Sales Revenue & VAT
 * ------------------------------------------------------------------------- */

test('F01: service report correctly calculates turnaround time, status counts, and distinguishes charges from sales revenue', function () {
    $owner = p2b2Owner();
    [, $customer] = p2b2CustomerUser();

    $tz = 'Asia/Manila';
    $today = Carbon::now($tz)->format('Y-m-d');

    // 1. Completed job with 2 hours turnaround
    $job1 = new Repair([
        'customer_id'   => $customer->id,
        'customer_name' => $customer->name,
        'bike_model'    => 'Yamaha Aerox',
        'issue'         => 'CVT Cleaning and Tuning',
        'status'        => 'done',
        'cost'          => 1200.00,
        'is_paid'       => true,
        'started_at'    => Carbon::parse("{$today} 10:00:00", $tz),
        'completed_at'  => Carbon::parse("{$today} 12:00:00", $tz), // 120 mins
    ]);
    $job1->paid_at = Carbon::parse("{$today} 12:05:00", $tz);
    $job1->created_at = Carbon::parse("{$today} 09:30:00", $tz);
    $job1->save();

    // 2. Completed job with 1 hour turnaround
    $job2 = new Repair([
        'customer_id'   => $customer->id,
        'customer_name' => $customer->name,
        'bike_model'    => 'Honda Click 125',
        'issue'         => 'Oil change & brake pads',
        'status'        => 'completed',
        'cost'          => 800.00,
        'is_paid'       => true,
        'started_at'    => Carbon::parse("{$today} 13:00:00", $tz),
        'completed_at'  => Carbon::parse("{$today} 14:00:00", $tz), // 60 mins
    ]);
    $job2->paid_at = Carbon::parse("{$today} 14:10:00", $tz);
    $job2->created_at = Carbon::parse("{$today} 12:30:00", $tz);
    $job2->save();

    // 3. Ongoing job (no completion date)
    $job3 = new Repair([
        'customer_id'   => $customer->id,
        'customer_name' => $customer->name,
        'bike_model'    => 'Kawasaki Ninja 400',
        'issue'         => 'Engine valve adjustment',
        'status'        => 'ongoing',
        'cost'          => 3500.00,
        'is_paid'       => false,
        'started_at'    => Carbon::parse("{$today} 09:00:00", $tz),
    ]);
    $job3->created_at = Carbon::parse("{$today} 08:30:00", $tz);
    $job3->save();

    // 4. Pending job
    $job4 = new Repair([
        'customer_id'   => $customer->id,
        'customer_name' => $customer->name,
        'bike_model'    => 'Suzuki Raider 150',
        'issue'         => 'Carb overhaul',
        'status'        => 'pending',
        'cost'          => 750.00,
        'is_paid'       => false,
    ]);
    $job4->created_at = Carbon::parse("{$today} 09:00:00", $tz);
    $job4->save();

    // 5. Authoritative SaleHeader recording actual cash received for repair ($2,000 gross)
    SaleHeader::create([
        'transaction_reference' => 'REPAIR-20261110-001',
        'customer_id'           => $customer->id,
        'transaction_type'      => SaleHeader::TYPE_REPAIR,
        'total_amount'          => 2000.00,
        'amount_tendered'       => 2000.00,
        'change_amount'         => 0.00,
        'payment_confirmed_at'  => Carbon::parse("{$today} 14:15:00", $tz),
    ]);

    $service = app(RepairReportService::class);
    $reportData = $service->getReportData([
        'date_from' => $today,
        'date_to'   => $today,
    ]);

    // Average turnaround of job1 (120m) and job2 (60m) = 90 mins = 1h 30m
    expect($reportData['total_jobs'])->toBe(4)
        ->and($reportData['status_counts']['ongoing'])->toBe(1)
        ->and($reportData['status_counts']['pending'])->toBe(1)
        ->and($reportData['status_counts']['completed'])->toBe(2)
        ->and($reportData['avg_turnaround_minutes'])->toBe(90)
        ->and($reportData['avg_turnaround_formatted'])->toBe('1h 30m');

    // Invoiced repair charges (1200 + 800 + 3500 + 750 = 6250)
    expect($reportData['total_invoiced_charges'])->toBe(6250.0)
        ->and($reportData['paid_invoiced_charges'])->toBe(2000.0)
        ->and($reportData['unpaid_invoiced_charges'])->toBe(4250.0);

    // Actual recorded cash sales revenue from SaleHeader = 2000.00 (with 12% VAT breakdown)
    expect($reportData['recorded_gross_revenue'])->toBe(2000.0)
        ->and($reportData['recorded_payment_transactions'])->toBe(1)
        ->and($reportData['vat_rate'])->toEqual(12)
        ->and(round($reportData['vat_net'], 2))->toBe(1785.71)
        ->and(round($reportData['vat_amount'], 2))->toBe(214.29);

    // Web view rendering
    $this->actingAs($owner)->get("/repairs/reports?date_from={$today}&date_to={$today}")
        ->assertOk()
        ->assertSee('1h 30m')
        ->assertSee('6,250.00')
        ->assertSee('2,000.00')
        ->assertSee('1,785.71')
        ->assertSee('214.29');
});

/* -------------------------------------------------------------------------
 | F01 / F08: Half-Open Manila Date Boundaries & Historical paid_at Handling
 * ------------------------------------------------------------------------- */

test('F01: date range boundary follows Asia/Manila half-open [startOfDay, startOfNextDay) and handles historical null paid_at correctly', function () {
    $owner = p2b2Owner();
    [, $customer] = p2b2CustomerUser();

    $tz = 'Asia/Manila';

    // Transaction at midnight Nov 12 (00:00:00) -> INCLUDED
    $r1 = new Repair([
        'customer_id'   => $customer->id,
        'customer_name' => $customer->name,
        'bike_model'    => 'Yamaha NMAX',
        'issue'         => 'Midnight repair',
        'status'        => 'pending',
        'cost'          => 500.00,
    ]);
    $r1->created_at = Carbon::parse('2026-11-12 00:00:00', $tz);
    $r1->save();

    // Transaction at 23:59:59.500000 Nov 12 -> INCLUDED
    $r2 = new Repair([
        'customer_id'   => $customer->id,
        'customer_name' => $customer->name,
        'bike_model'    => 'Honda Beat',
        'issue'         => 'Late night repair',
        'status'        => 'ongoing',
        'cost'          => 600.00,
    ]);
    $r2->created_at = Carbon::parse('2026-11-12 23:59:59', $tz);
    $r2->save();

    // Transaction at 00:00:00 Nov 13 -> EXCLUDED from Nov 12 single-day query
    $r3 = new Repair([
        'customer_id'   => $customer->id,
        'customer_name' => $customer->name,
        'bike_model'    => 'Suzuki Smash',
        'issue'         => 'Next morning repair',
        'status'        => 'done',
        'cost'          => 700.00,
    ]);
    $r3->created_at = Carbon::parse('2026-11-13 00:00:00', $tz);
    $r3->save();

    // Historical paid repair with NULL paid_at (from before Phase 2)
    $rHistorical = new Repair([
        'customer_id'   => $customer->id,
        'customer_name' => $customer->name,
        'bike_model'    => 'Legacy Bike',
        'issue'         => 'Historical paid job',
        'status'        => 'completed',
        'cost'          => 400.00,
        'is_paid'       => true,
    ]);
    $rHistorical->created_at = Carbon::parse('2026-11-12 12:00:00', $tz);
    $rHistorical->save();
    expect($rHistorical->paid_at)->toBeNull();

    $service = app(RepairReportService::class);

    // Single day range Nov 12
    $reportNov12 = $service->getReportData([
        'date_from' => '2026-11-12',
        'date_to'   => '2026-11-12',
        'date_type' => 'created',
    ]);

    // r1, r2, and rHistorical are included in Nov 12 created_at query (r3 is excluded)
    expect($reportNov12['total_jobs'])->toBe(3)
        ->and($reportNov12['historical_unstamped_count'])->toBe(1);

    // When querying by date_type = 'paid', historical repair with null paid_at is NOT matched
    // and no fabricated timestamp is used
    $reportPaidNov12 = $service->getReportData([
        'date_from' => '2026-11-12',
        'date_to'   => '2026-11-12',
        'date_type' => 'paid',
    ]);
    expect($reportPaidNov12['total_jobs'])->toBe(0)
        ->and($reportPaidNov12['historical_unstamped_count'])->toBe(1);

    // Reversed date range (from > to) cleanly yields 0 jobs without error
    $reversed = $service->getReportData([
        'date_from' => '2026-11-15',
        'date_to'   => '2026-11-10',
    ]);
    expect($reversed['total_jobs'])->toBe(0);
});

/* -------------------------------------------------------------------------
 | F14: Report Exports (PDF, CSV, UTF-8 BOM, Formula Injection Defense)
 * ------------------------------------------------------------------------- */

test('F14: authorized owner can export PDF service report', function () {
    $owner = p2b2Owner();
    [, $customer] = p2b2CustomerUser();

    Repair::create([
        'customer_id'   => $customer->id,
        'customer_name' => $customer->name,
        'bike_model'    => 'Honda Click',
        'issue'         => 'Belt replacement',
        'status'        => 'done',
        'cost'          => 1500.00,
        'is_paid'       => true,
    ]);

    $response = $this->actingAs($owner)->get(route('repairs.reports.export.pdf'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    // Customer forbidden from PDF export
    [$customerUser] = p2b2CustomerUser();
    $this->actingAs($customerUser)->get(route('repairs.reports.export.pdf'))->assertForbidden();
});

test('F14: authorized owner can export CSV report with UTF-8 BOM and formula injection protection', function () {
    $owner = p2b2Owner();
    [, $customer] = p2b2CustomerUser();

    // Malicious injection attempt in customer_name and issue
    $injectedRepair = Repair::create([
        'customer_id'   => $customer->id,
        'customer_name' => '=CMD|\' /C calc\'!A0',
        'bike_model'    => '+2+5',
        'issue'         => '@SUM(1,2)',
        'status'        => 'ongoing',
        'cost'          => 999.00,
        'is_paid'       => false,
    ]);

    // Sanitize helper unit verification
    expect(RepairReportService::sanitizeCsvField('=CMD|calc'))->toBe("'=CMD|calc")
        ->and(RepairReportService::sanitizeCsvField('+12345'))->toBe("'+12345")
        ->and(RepairReportService::sanitizeCsvField('-12345'))->toBe("'-12345")
        ->and(RepairReportService::sanitizeCsvField('@eval'))->toBe("'@eval")
        ->and(RepairReportService::sanitizeCsvField("Normal Customer"))->toBe("Normal Customer");

    // CSV Streamed Download test
    $response = $this->actingAs($owner)->get(route('repairs.reports.export.csv'));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/csv');

    $csvContent = $response->streamedContent();

    // Verify UTF-8 BOM is present at byte 0
    expect(str_starts_with($csvContent, "\xEF\xBB\xBF"))->toBeTrue();

    // Verify neutralized formula cells start with single quote
    expect($csvContent)->toContain("'=CMD|' /C calc'!A0")
        ->and($csvContent)->toContain("'+2+5")
        ->and($csvContent)->toContain("'@SUM(1,2)");

    // Customer forbidden from CSV export
    [$customerUser] = p2b2CustomerUser();
    $this->actingAs($customerUser)->get(route('repairs.reports.export.csv'))->assertForbidden();
});

test('F14: CSV export matches web report query filters (export parity)', function () {
    $owner = p2b2Owner();
    [, $customer1] = p2b2CustomerUser();
    [, $customer2] = p2b2CustomerUser();

    Repair::create([
        'customer_id'   => $customer1->id,
        'customer_name' => 'Target Customer',
        'bike_model'    => 'Yamaha Sniper',
        'issue'         => 'Radiator flush',
        'status'        => 'pending',
        'cost'          => 450.00,
        'is_paid'       => false,
    ]);

    Repair::create([
        'customer_id'   => $customer2->id,
        'customer_name' => 'Other Customer',
        'bike_model'    => 'Honda ADV',
        'issue'         => 'Brake fluid',
        'status'        => 'ongoing',
        'cost'          => 600.00,
        'is_paid'       => false,
    ]);

    // Filter by customer_id
    $response = $this->actingAs($owner)->get(route('repairs.reports.export.csv', [
        'customer_id' => $customer1->id,
    ]));

    $response->assertOk();
    $csvContent = $response->streamedContent();

    expect($csvContent)->toContain('Target Customer')
        ->and($csvContent)->toContain('Yamaha Sniper')
        ->and($csvContent)->not->toContain('Other Customer')
        ->and($csvContent)->not->toContain('Honda ADV');
});

/* -------------------------------------------------------------------------
 | F10: Customer Maintenance Updates, Fleet Grouping, and IDOR Prevention
 * ------------------------------------------------------------------------- */

test('F10: authenticated customer can view their own motorcycle maintenance history and summary cards', function () {
    [$user, $customer] = p2b2CustomerUser();

    // Service 1 for Yamaha NMAX
    Repair::create([
        'customer_id'   => $customer->id,
        'customer_name' => $customer->name,
        'bike_model'    => 'Yamaha NMAX',
        'issue'         => 'First maintenance checkup',
        'status'        => 'done',
        'cost'          => 850.00,
        'is_paid'       => true,
        'started_at'    => Carbon::now()->subDays(5),
        'completed_at'  => Carbon::now()->subDays(5)->addHours(2),
    ]);

    // Service 2 for Yamaha NMAX (ongoing)
    Repair::create([
        'customer_id'   => $customer->id,
        'customer_name' => $customer->name,
        'bike_model'    => 'Yamaha NMAX',
        'issue'         => 'CVT cleaning and belt inspection',
        'status'        => 'ongoing',
        'cost'          => 1100.00,
        'is_paid'       => false,
        'started_at'    => Carbon::now()->subHours(1),
    ]);

    // Service for Honda Beat
    Repair::create([
        'customer_id'   => $customer->id,
        'customer_name' => $customer->name,
        'bike_model'    => 'Honda Beat',
        'issue'         => 'Spark plug change',
        'status'        => 'pending',
        'cost'          => 250.00,
        'is_paid'       => false,
    ]);

    $response = $this->actingAs($user)->get('/customer/dashboard?tab=repairs');

    $response->assertOk()
        ->assertSee('Track My Repairs')
        ->assertSee('Motorcycle Service History')
        ->assertSee('Total Services')
        ->assertSee('Yamaha NMAX')
        ->assertSee('Honda Beat')
        ->assertSee('CVT cleaning and belt inspection')
        ->assertSee('₱1,100.00')
        ->assertSee('Details');

    // Customer view MUST NOT expose internal administrative controls
    $response->assertDontSee('data-repair-edit')
        ->assertDontSee('editRepairModal')
        ->assertDontSee('name="started_at"')
        ->assertDontSee('name="estimated_minutes"');
});

test('F10: customer can view details of their own repair via /customer/repairs/{id} but other customers are rejected (IDOR protection)', function () {
    [$user1, $customer1] = p2b2CustomerUser();
    [$user2, $customer2] = p2b2CustomerUser();

    $repairCustomer1 = Repair::create([
        'customer_id'   => $customer1->id,
        'customer_name' => $customer1->name,
        'bike_model'    => 'Yamaha Sniper 155',
        'issue'         => 'Chain adjustment & sprockets',
        'status'        => 'ongoing',
        'cost'          => 1250.00,
        'is_paid'       => false,
        'started_at'    => Carbon::now()->subMinutes(30),
    ]);

    // 1. Customer 1 can view their own repair
    $res1 = $this->actingAs($user1)->getJson(route('customer.repairs.show', $repairCustomer1));
    $res1->assertOk()
        ->assertJson([
            'id'         => $repairCustomer1->id,
            'bike_model' => 'Yamaha Sniper 155',
            'issue'      => 'Chain adjustment & sprockets',
            'status'     => 'ongoing',
            'cost'       => 1250.0,
            'is_paid'    => false,
            'is_ongoing' => true,
        ]);

    // 2. Customer 2 ATTEMPTS to view Customer 1's repair (IDOR attack) -> MUST BE 403 FORBIDDEN
    $res2 = $this->actingAs($user2)->getJson(route('customer.repairs.show', $repairCustomer1));
    $res2->assertForbidden();

    // 3. Unauthenticated guest is redirected to login
    auth()->logout();
    $this->get(route('customer.repairs.show', $repairCustomer1))->assertRedirect(route('login'));

    // 4. Shop owner CAN view via endpoint
    $owner = p2b2Owner();
    $this->actingAs($owner)->getJson(route('customer.repairs.show', $repairCustomer1))->assertOk();
});

test('F10: customer repair display supports all valid statuses: pending, ongoing, done, and legacy completed', function () {
    [$user, $customer] = p2b2CustomerUser();

    $statuses = ['pending', 'ongoing', 'done', 'completed'];

    foreach ($statuses as $status) {
        Repair::create([
            'customer_id'   => $customer->id,
            'customer_name' => $customer->name,
            'bike_model'    => "Bike-{$status}",
            'issue'         => "Testing status {$status}",
            'status'        => $status,
            'cost'          => 500.00,
            'is_paid'       => in_array($status, ['done', 'completed']),
        ]);
    }

    $response = $this->actingAs($user)->get('/customer/dashboard?tab=repairs');

    $response->assertOk();
    foreach ($statuses as $status) {
        $response->assertSee("Bike-{$status}")
            ->assertSee($status);
    }
});

/* -------------------------------------------------------------------------
 | Final Integration & Data Integrity Regression Tests
 * ------------------------------------------------------------------------- */

test('F01: turnaround time calculation excludes invalid negative intervals instead of concealing them with abs()', function () {
    $tz = 'Asia/Manila';
    $today = Carbon::now($tz)->format('Y-m-d');
    [, $customer] = p2b2CustomerUser();

    // 1. Valid job: 60 minutes
    $validJob = new Repair([
        'customer_id'   => $customer->id,
        'customer_name' => $customer->name,
        'bike_model'    => 'Valid Bike',
        'issue'         => 'Normal service',
        'status'        => 'done',
        'cost'          => 500.00,
        'is_paid'       => true,
        'started_at'    => Carbon::parse("{$today} 10:00:00", $tz),
        'completed_at'  => Carbon::parse("{$today} 11:00:00", $tz), // +60 mins
    ]);
    $validJob->created_at = Carbon::parse("{$today} 09:00:00", $tz);
    $validJob->save();

    // 2. Corrupted/invalid job: completed BEFORE started (negative interval: 14:00 to 12:00 = -120 mins)
    $corruptedJob = new Repair([
        'customer_id'   => $customer->id,
        'customer_name' => $customer->name,
        'bike_model'    => 'Corrupted Bike',
        'issue'         => 'Clock jump glitch',
        'status'        => 'done',
        'cost'          => 600.00,
        'is_paid'       => true,
        'started_at'    => Carbon::parse("{$today} 14:00:00", $tz),
        'completed_at'  => Carbon::parse("{$today} 12:00:00", $tz), // Invalid negative!
    ]);
    $corruptedJob->created_at = Carbon::parse("{$today} 09:00:00", $tz);
    $corruptedJob->save();

    $service = app(RepairReportService::class);
    $reportData = $service->getReportData([
        'date_from' => $today,
        'date_to'   => $today,
    ]);

    // Total completed jobs is 2, but only 1 has a valid interval
    expect($reportData['status_counts']['completed'])->toBe(2)
        ->and($reportData['invalid_turnaround_count'])->toBe(1)
        // If abs() had been used, average would be (60 + 120) / 2 = 90 mins.
        // With invalid interval excluded, average is strictly 60 mins.
        ->and($reportData['avg_turnaround_minutes'])->toBe(60)
        ->and($reportData['avg_turnaround_formatted'])->toBe('1h 0m');
});

test('F03: paid repair rejects customer_id reassignment, customer_name alteration, and status reversion to pending/ongoing', function () {
    $owner = p2b2Owner();
    [, $customer1] = p2b2CustomerUser();
    [, $customer2] = p2b2CustomerUser();

    $paidRepair = Repair::create([
        'customer_id'   => $customer1->id,
        'customer_name' => $customer1->name,
        'bike_model'    => 'Yamaha Aerox',
        'issue'         => 'Transmission overhaul',
        'cost'          => 2500.00,
        'status'        => 'done',
        'is_paid'       => true,
    ]);
    $paidRepair->paid_at = Carbon::now();
    $paidRepair->save();

    // 1. Attempting to reassign customer_id on paid repair is rejected
    $resCustId = $this->actingAs($owner)->put(route('repairs.update', $paidRepair), [
        'customer_id' => $customer2->id,
        'bike_model'  => 'Yamaha Aerox',
        'issue'       => 'Transmission overhaul',
        'cost'        => 2500.00,
        'status'      => 'done',
    ]);
    $resCustId->assertRedirect('/repairs')
        ->assertSessionHas('error', 'This repair is already paid, so its customer cannot be reassigned.');
    expect($paidRepair->fresh()->customer_id)->toBe($customer1->id);

    // 2. Attempting to alter customer_name on paid repair is rejected
    $resCustName = $this->actingAs($owner)->put(route('repairs.update', $paidRepair), [
        'customer_name' => 'Hacked New Name',
        'bike_model'    => 'Yamaha Aerox',
        'issue'         => 'Transmission overhaul',
        'cost'          => 2500.00,
        'status'        => 'done',
    ]);
    $resCustName->assertRedirect('/repairs')
        ->assertSessionHas('error', 'This repair is already paid, so its customer cannot be reassigned.');
    expect($paidRepair->fresh()->customer_name)->toBe($customer1->name);

    // 3. Attempting to revert status of paid repair back to pending or ongoing is rejected
    foreach (['pending', 'ongoing'] as $revertStatus) {
        $resStatus = $this->actingAs($owner)->put(route('repairs.update', $paidRepair), [
            'status'     => $revertStatus,
            'bike_model' => 'Yamaha Aerox',
            'cost'       => 2500.00,
        ]);
        $resStatus->assertRedirect('/repairs')
            ->assertSessionHas('error', "This repair is already paid, so its status cannot be moved back to {$revertStatus}.");
        expect($paidRepair->fresh()->status)->toBe('done');
    }

    // 4. Updating descriptive issue on paid repair without altering customer or financial fields succeeds
    $resOk = $this->actingAs($owner)->put(route('repairs.update', $paidRepair), [
        'issue'      => 'Transmission overhaul and new oil seal installed',
        'bike_model' => 'Yamaha Aerox 155 V2',
        'cost'       => 2500.00,
        'status'     => 'done',
    ]);
    $resOk->assertRedirect('/repairs')
        ->assertSessionHas('success');
    expect($paidRepair->fresh()->issue)->toBe('Transmission overhaul and new oil seal installed')
        ->and($paidRepair->fresh()->bike_model)->toBe('Yamaha Aerox 155 V2');
});

test('F14: CSV formula injection defense neutralizes cells with leading spaces, tabs, carriage returns, and formula symbols', function () {
    // Tests various OWASP CSV injection payloads with leading whitespace and control characters
    expect(RepairReportService::sanitizeCsvField(" =1+1"))->toBe("' =1+1")
        ->and(RepairReportService::sanitizeCsvField("   @SUM(A1:A10)"))->toBe("'   @SUM(A1:A10)")
        ->and(RepairReportService::sanitizeCsvField("\t-500"))->toBe("'\t-500")
        ->and(RepairReportService::sanitizeCsvField("\r+100"))->toBe("'\r+100")
        ->and(RepairReportService::sanitizeCsvField("%SystemRoot%"))->toBe("'%SystemRoot%")
        ->and(RepairReportService::sanitizeCsvField("   %USERNAME%"))->toBe("'   %USERNAME%")
        ->and(RepairReportService::sanitizeCsvField("Safe Customer Name"))->toBe("Safe Customer Name")
        ->and(RepairReportService::sanitizeCsvField("   Normal Spaced Text"))->toBe("   Normal Spaced Text");
});
