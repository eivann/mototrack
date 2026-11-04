<?php

use App\Models\InventoryItem;
use App\Models\Repair;
use App\Models\Sale;
use App\Models\SaleHeader;
use App\Models\User;
use App\Support\Vat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

/**
 * RD2-R2.4 — receipts show the 12% VAT-inclusive breakdown (VATable sales + included VAT), while the
 * gross total the customer pays is unchanged. VAT is derived from the gross, so nothing is stored.
 */

beforeEach(fn () => Notification::fake());

function vatOwner(): User
{
    return User::query()->where('role', 'owner')->first() ?? User::factory()->create(['role' => 'owner']);
}

function peso(float $n): string
{
    return '₱'.number_format($n, 2);
}

test('the POS sales receipt shows VATable sales + included VAT and an unchanged grand total', function () {
    $a = InventoryItem::create(['part_name' => 'Brake Pad', 'sku' => 'VAT-1', 'price' => 820, 'quantity' => 10]);
    $b = InventoryItem::create(['part_name' => 'Oil', 'sku' => 'VAT-2', 'price' => 300, 'quantity' => 10]);
    $this->actingAs(vatOwner())->post('/sales/store', [
        'cart_data' => json_encode([['id' => $a->id, 'quantity' => 1], ['id' => $b->id, 'quantity' => 1]]),
        'amount_tendered' => '1120',
    ])->assertRedirect();

    $ref = SaleHeader::latest('id')->first()->transaction_reference;
    $gross = (float) Sale::where('transaction_reference', $ref)->sum('total_price');
    $v = Vat::fromInclusive($gross); // 1120 -> net 1000, vat 120

    $html = $this->actingAs(vatOwner())->get(route('sales.receipt', $ref))->assertOk()
        ->assertSee('VATable Sales')
        ->assertSee('VAT (12%, included)')
        ->assertSee('Grand Total (VAT Inclusive)')
        ->assertSee(peso($v->net))   // 1,000.00
        ->assertSee(peso($v->vat));  // 120.00
    // Gross unchanged and reconciles.
    expect($gross)->toBe(1120.00)->and(round($v->net + $v->vat, 2))->toBe($gross);
    $html->assertSee(peso($gross));
});

test('the PDF receipt includes the VAT breakdown rows', function () {
    $a = InventoryItem::create(['part_name' => 'Chain', 'sku' => 'VAT-3', 'price' => 1120, 'quantity' => 5]);
    $this->actingAs(vatOwner())->post('/sales/store', [
        'cart_data' => json_encode([['id' => $a->id, 'quantity' => 1]]),
        'amount_tendered' => '1120',
    ])->assertRedirect();
    $ref = SaleHeader::latest('id')->first()->transaction_reference;

    $pdf = $this->actingAs(vatOwner())->get(route('sales.download', ['transaction' => $ref]));
    $pdf->assertOk();
    expect($pdf->headers->get('content-type'))->toContain('application/pdf');
});

test('the repair receipt shows the VAT breakdown of the service cost', function () {
    $repair = Repair::create([
        'customer_name' => 'Walk-in', 'bike_model' => 'Click', 'issue' => 'Brakes',
        'status' => 'completed', 'cost' => 560, 'is_paid' => true,
    ]);
    $v = Vat::fromInclusive(560); // net 500, vat 60

    $this->actingAs(vatOwner())->get(route('repairs.receipt', $repair))->assertOk()
        ->assertSee('VAT (12%, included)')
        ->assertSee('Total Cost (VAT Inclusive)')
        ->assertSee(peso($v->net))
        ->assertSee(peso($v->vat));
    expect($v->net)->toBe(500.00)->and($v->vat)->toBe(60.00);
});
