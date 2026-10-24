<?php

use App\Actions\CommitOrder;
use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Paid PDF receipts say "GRAND TOTAL:"; unpaid reservations keep "TOTAL DUE:".
 * The HTML handed to DomPDF is rendered with the same variables the controllers pass,
 * because DomPDF compresses the text inside the PDF bytes.
 */

function pdfItem(string $name, float $price, string $sku): InventoryItem
{
    return InventoryItem::create(['part_name' => $name, 'sku' => $sku, 'price' => $price, 'quantity' => 20]);
}

/** Cash sale through the real POS commit path: tender stamped on the first line. */
function pdfCashSale(string $ref, array $lines, float $tendered): void
{
    app(CommitOrder::class)->execute(
        $lines, $ref, null, 'Out of stock: ',
        fn ($first, $total) => $first ? $tendered : 0.0,
        fn ($first, $total) => $first ? round($tendered - $total, 2) : 0.0,
        false, 'completed'
    );
}

/** Online reservation through the real commit path: pending, nothing collected. */
function pdfReservation(string $ref, array $lines): void
{
    app(CommitOrder::class)->execute(
        $lines, $ref, null, 'Out of stock: ',
        fn () => 0.0, fn () => 0.0, false, 'pending'
    );
}

/** Render the PDF template exactly as the download controllers feed it. */
function pdfHtml(string $ref): string
{
    $sales = Sale::with('item')->where('transaction_reference', $ref)->get();

    return view('sales.pdf_receipt', [
        'sales' => $sales,
        'transactionId' => $ref,
        'date' => $sales->first()->created_at->format('F d, Y h:i A'),
    ])->render();
}

test('completed cash sale PDF shows GRAND TOTAL and not TOTAL DUE, amounts unchanged', function () {
    $part = pdfItem('Brake Pad', 250, 'PDF-1');
    pdfCashSale('TRX-PDF-PAID', [['id' => $part->id, 'quantity' => 1]], 300);

    $html = pdfHtml('TRX-PDF-PAID');

    expect($html)->toContain('GRAND TOTAL:')
        ->and($html)->not->toContain('TOTAL DUE')
        ->and($html)->toContain('Cash Sales Receipt')
        ->and($html)->toContain('PHP 250.00')   // subtotal and grand total
        ->and($html)->toContain('Amount Tendered:')
        ->and($html)->toContain('PHP 300.00')
        ->and($html)->toContain('Change Given:')
        ->and($html)->toContain('PHP 50.00')
        ->and(substr_count($html, 'PHP 250.00'))->toBeGreaterThanOrEqual(3); // line unit price, line total, subtotal, grand total
});

test('unpaid reservation PDF keeps TOTAL DUE with the full three-item total', function () {
    $a = pdfItem('Chain', 100, 'PDF-2');
    $b = pdfItem('Spark Plug', 120, 'PDF-3');
    $c = pdfItem('Grip', 110, 'PDF-4');
    pdfReservation('TRX-PDF-RES', [
        ['id' => $a->id, 'quantity' => 1], ['id' => $b->id, 'quantity' => 1], ['id' => $c->id, 'quantity' => 2],
    ]); // 100 + 120 + (2 x 110) = 440

    $html = pdfHtml('TRX-PDF-RES');

    expect($html)->toContain('TOTAL DUE:')
        ->and($html)->not->toContain('GRAND TOTAL')
        ->and($html)->toContain('Reservation Acknowledgement')
        ->and($html)->toContain('Amount Collected:')
        ->and($html)->toContain('Cash at Pickup')
        ->and($html)->toContain('PHP 440.00')
        ->and($html)->not->toContain('Amount Tendered:');
});

test('a reservation completed at the counter switches to GRAND TOTAL with tender and change', function () {
    $a = pdfItem('Mirror', 150, 'PDF-5');
    $b = pdfItem('Bulb', 100, 'PDF-6');
    pdfReservation('TRX-PDF-PICKUP', [['id' => $a->id, 'quantity' => 1], ['id' => $b->id, 'quantity' => 1]]);

    expect(pdfHtml('TRX-PDF-PICKUP'))->toContain('TOTAL DUE:');

    $owner = User::factory()->create(['role' => 'owner']);
    $this->actingAs($owner)->post('/sales/TRX-PDF-PICKUP/complete', ['amount_tendered' => 300])->assertRedirect();

    $html = pdfHtml('TRX-PDF-PICKUP');

    expect($html)->toContain('GRAND TOTAL:')
        ->and($html)->not->toContain('TOTAL DUE')
        ->and($html)->toContain('PHP 250.00')
        ->and($html)->toContain('PHP 300.00')
        ->and($html)->toContain('PHP 50.00');
});

test('existing PDF download routes still return a PDF for paid and unpaid transactions', function () {
    $part = pdfItem('Clutch Cable', 250, 'PDF-7');
    pdfCashSale('TRX-PDF-ROUTE-PAID', [['id' => $part->id, 'quantity' => 1]], 300);
    pdfReservation('TRX-PDF-ROUTE-RES', [['id' => $part->id, 'quantity' => 1]]);

    $owner = User::factory()->create(['role' => 'owner']);
    $paidId = Sale::where('transaction_reference', 'TRX-PDF-ROUTE-PAID')->value('id');
    $resId = Sale::where('transaction_reference', 'TRX-PDF-ROUTE-RES')->value('id');

    $urls = [
        route('sales.download', ['transaction' => 'TRX-PDF-ROUTE-PAID']),
        route('sales.download', ['transaction' => 'TRX-PDF-ROUTE-RES']),
        route('sales.download.by-id', ['id' => $paidId]),
        route('sales.download.by-id', ['id' => $resId]),
        route('checkout.receipt.download', ['transaction' => 'TRX-PDF-ROUTE-PAID']),
        route('checkout.receipt.download', ['transaction' => 'TRX-PDF-ROUTE-RES']),
    ];

    foreach ($urls as $url) {
        $response = $this->actingAs($owner)->get($url);
        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        expect(substr($response->getContent(), 0, 5))->toBe('%PDF-');
    }
});
