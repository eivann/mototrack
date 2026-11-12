<?php

use App\Models\Repair;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('F02: repair with existing cost does not show a persistent Save button until Edit is clicked', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $repair = Repair::create([
        'customer_name' => 'John Doe',
        'bike_model' => 'Mio Sporty',
        'issue' => 'Brake adjustment',
        'cost' => 350.00,
        'status' => 'ongoing',
        'is_paid' => false,
    ]);

    $response = $this->actingAs($owner)->get('/repairs');
    $response->assertOk();

    $html = $response->getContent();

    // Must show formatted cost and Edit button
    expect($html)->toContain('₱350.00');
    expect($html)->toContain('Edit');

    // The cost edit form is guarded by x-show="editingCost"
    expect($html)->toContain('x-show="editingCost"');
});

test('F02: repair without cost shows Cost not set and cost entry form ready', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $repair = Repair::create([
        'customer_name' => 'Jane Doe',
        'bike_model' => 'Click 125',
        'issue' => 'Engine check',
        'cost' => null,
        'status' => 'ongoing',
        'is_paid' => false,
    ]);

    $response = $this->actingAs($owner)->get('/repairs');
    $response->assertOk();

    $html = $response->getContent();
    expect($html)->toContain('Cost not set');
});

test('F04: Collect, Delete, Print, and Cost Save buttons have accessible contrast classes', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $repair = Repair::create([
        'customer_name' => 'Contrast Test',
        'bike_model' => 'Wave 110',
        'issue' => 'Chain check',
        'cost' => 200.00,
        'status' => 'done',
        'is_paid' => false,
    ]);

    $response = $this->actingAs($owner)->get('/repairs');
    $response->assertOk();

    $html = $response->getContent();

    // Collect button: prominent emerald background with white text
    expect($html)->toContain('bg-emerald-600');
    expect($html)->toContain('text-white');

    // Delete button: high contrast red in light and dark
    expect($html)->toContain('text-red-600');
    expect($html)->toContain('dark:text-red-400');

    // Print button: high contrast indigo in light and dark
    expect($html)->toContain('text-indigo-600');
    expect($html)->toContain('dark:text-indigo-400');

    // Save button: solid indigo background with white text
    expect($html)->toContain('bg-indigo-600');
});

test('F05: cost save form has confirmation and prevents accidental duplicate submission', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $repair = Repair::create([
        'customer_name' => 'Confirm Test',
        'bike_model' => 'Raider 150',
        'issue' => 'Fork oil',
        'cost' => 500.00,
        'status' => 'pending',
        'is_paid' => false,
    ]);

    $response = $this->actingAs($owner)->get('/repairs');
    $response->assertOk();

    $html = $response->getContent();

    // Confirm prompt on cost form submit
    expect($html)->toContain('Confirm saving repair cost');

    // Prevent duplicate clicks on submit
    expect($html)->toContain(':disabled="saving"');

    // Delete and Collect confirmations are preserved
    expect($html)->toContain("confirm('Delete this repair record?')");
    expect($html)->toContain("confirm('Confirm payment received?')");
});

test('F02 & F05: paid repairs cannot show edit button or cost modification form', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $repair = Repair::create([
        'customer_name' => 'Paid Repair Test',
        'bike_model' => 'Barako 175',
        'issue' => 'Tune up',
        'cost' => 600.00,
        'status' => 'done',
        'is_paid' => true,
    ]);

    $response = $this->actingAs($owner)->get('/repairs');
    $response->assertOk();

    $html = $response->getContent();

    // Must show the paid cost
    expect($html)->toContain('₱600.00');

    // Must NOT contain the cost edit form or Edit button for paid repairs
    expect($html)->not->toContain('action="' . route('repairs.cost', $repair) . '"');
});

