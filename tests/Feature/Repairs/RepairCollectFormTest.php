<?php

use App\Models\Repair;
use App\Models\SaleHeader;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Final UAT defect: the Repairs "Collect" form never sent the cash tendered, so the server (which requires full cash
 * payment) rejected every real click with "Payment recording failed". The form must carry the amount field, and a
 * request containing exactly the form's fields must record the payment. Synthetic data only.
 */
uses(RefreshDatabase::class);

test('the Collect form carries the cash-received field, prefilled with the cost, and a form-shaped request records the payment', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $repair = Repair::create(['customer_name' => 'Form Test', 'bike_model' => 'Mio', 'issue' => 'Noise', 'cost' => 800, 'status' => 'done', 'is_paid' => false]);

    $html = $this->actingAs($owner)->get('/repairs')->assertOk()->getContent();
    expect($html)->toContain('name="amount_tendered"')
        ->and($html)->toMatch('/min="800(\.0+)?"/')->and($html)->toMatch('/value="800(\.0+)?"/');

    // Exactly what the browser submits for that form (token and method spoofing aside): the amount field alone.
    $this->actingAs($owner)->put("/repairs/{$repair->id}/pay", ['amount_tendered' => '800.00'])->assertSessionHas('success');
    expect((bool) $repair->fresh()->is_paid)->toBeTrue()
        ->and(SaleHeader::where('transaction_type', 'repair')->count())->toBe(1)
        ->and((float) SaleHeader::where('transaction_type', 'repair')->first()->total_amount)->toBe(800.0);

    // A second submission is refused and creates no second sale.
    $this->actingAs($owner)->put("/repairs/{$repair->id}/pay", ['amount_tendered' => '800.00'])->assertSessionHas('error');
    expect(SaleHeader::where('transaction_type', 'repair')->count())->toBe(1);
});

test('a repair with no cost shows an empty cash field and still cannot be collected', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $repair = Repair::create(['customer_name' => 'No Cost', 'bike_model' => 'Mio', 'issue' => 'Noise', 'cost' => null, 'status' => 'ongoing', 'is_paid' => false]);

    $this->actingAs($owner)->get('/repairs')->assertOk()->assertSee('Cost not set');
    $this->actingAs($owner)->put("/repairs/{$repair->id}/pay", ['amount_tendered' => '100'])->assertSessionHas('error', 'Enter the repair cost before collecting payment.');
    expect((bool) $repair->fresh()->is_paid)->toBeFalse()->and(SaleHeader::where('transaction_type', 'repair')->count())->toBe(0);
});
