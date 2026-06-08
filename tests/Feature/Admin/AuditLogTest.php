<?php

use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('owner can access system audit logs page', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $response = $this->actingAs($owner)->get('/admin/audit-logs');
    $response->assertStatus(200);
});

test('non-owner cannot access system audit logs page', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $response = $this->actingAs($user)->get('/admin/audit-logs');
    $response->assertStatus(403);
});

test('creating updating deleting audit trail-bound model generates audit logs', function () {
    $this->withoutExceptionHandling();
    $owner = User::factory()->create(['role' => 'owner']);
    
    // Test Create
    $item = $this->actingAs($owner)->post('/inventory', [
        'sku' => 'SKU-AUDIT-1',
        'part_name' => 'Chain lube',
        'category' => 'Lubes',
        'brand' => 'Yamalube',
        'model' => 'Universal',
        'price' => 250.00,
        'quantity' => 20,
        'low_stock_threshold' => 5,
    ]);

    $itemInstance = InventoryItem::where('sku', 'SKU-AUDIT-1')->first();
    expect($itemInstance)->not->toBeNull();

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'created',
        'auditable_type' => InventoryItem::class,
        'auditable_id' => $itemInstance->id,
        'user_id' => $owner->id,
    ]);

    // Test Update
    $response = $this->actingAs($owner)->put("/inventory/{$itemInstance->id}", [
        'sku' => 'SKU-AUDIT-1',
        'part_name' => 'Premium lube',
        'category' => 'Lubes',
        'brand' => 'Yamalube',
        'model' => 'Universal',
        'price' => 300.00,
        'quantity' => 20,
        'low_stock_threshold' => 5,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'updated',
        'auditable_type' => InventoryItem::class,
        'auditable_id' => $itemInstance->id,
    ]);

    $updateLog = AuditLog::where('action', 'updated')
        ->where('auditable_type', InventoryItem::class)
        ->where('auditable_id', $itemInstance->id)
        ->first();

    expect($updateLog->old_values['part_name'])->toBe('Chain Lube')
        ->and($updateLog->new_values['part_name'])->toBe('Premium Lube')
        ->and((float)$updateLog->old_values['price'])->toBe(250.00)
        ->and((float)$updateLog->new_values['price'])->toBe(300.00);

    // Test Delete
    $this->actingAs($owner)->delete("/inventory/{$itemInstance->id}");

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'deleted',
        'auditable_type' => InventoryItem::class,
        'auditable_id' => $itemInstance->id,
    ]);
});

test('soft-deleting and restoring models logs correct action events without duplication', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $item = InventoryItem::create([
        'part_name' => 'Tire',
        'price' => 1500,
        'quantity' => 10,
    ]);

    $sale = Sale::create([
        'inventory_item_id' => $item->id,
        'quantity' => 1,
        'price' => 1500,
        'total_price' => 1500,
        'transaction_reference' => 'TRX-AUDIT-SOFT',
    ]);

    // Clear logs from creation to isolate soft delete
    AuditLog::truncate();

    // Trigger soft delete
    $sale->delete();

    // Assert that we have exactly one soft_deleted log, and no updated logs for deleted_at
    $logs = AuditLog::all();
    expect($logs)->toHaveCount(1)
        ->and($logs->first()->action)->toBe('soft_deleted');

    // Trigger restore
    $sale->restore();

    $restoreLogs = AuditLog::where('action', 'restored')->get();
    expect($restoreLogs)->toHaveCount(1);
});

test('audit logs are pruneable', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    
    // Create an old log
    $oldLog = AuditLog::create([
        'user_id' => $owner->id,
        'action' => 'created',
        'auditable_type' => User::class,
        'auditable_id' => $owner->id,
        'created_at' => now()->subDays(190),
    ]);

    // Create a new log
    $newLog = AuditLog::create([
        'user_id' => $owner->id,
        'action' => 'created',
        'auditable_type' => User::class,
        'auditable_id' => $owner->id,
        'created_at' => now()->subDays(10),
    ]);

    // Run pruning
    $prunedCount = (new AuditLog)->pruneAll();
    expect($prunedCount)->toBe(1);

    $this->assertDatabaseMissing('audit_logs', ['id' => $oldLog->id]);
    $this->assertDatabaseHas('audit_logs', ['id' => $newLog->id]);
});
