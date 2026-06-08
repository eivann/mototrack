<?php

use App\Models\Sale;
use App\Models\User;
use App\Models\InventoryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can download a pdf receipt', function () {
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
        'transaction_reference' => 'TRX-123456',
    ]);

    $response = $this->actingAs($owner)->get("/sales/TRX-123456/download");
    
    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/pdf');
    $this->assertStringContainsString('attachment; filename=Receipt_TRX-123456.pdf', $response->headers->get('content-disposition'));
});
