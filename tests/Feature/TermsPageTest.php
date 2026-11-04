<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * RD2-R5: the public Terms and Conditions page states only confirmed policies and follows the GCash QR gate.
 */

test('the terms page is public and describes cash, reservations, pre-orders, cancellation and same-day service requests', function () {
    $html = $this->get('/terms')->assertOk()->getContent();

    expect($html)
        ->toContain('Terms and Conditions')
        ->toContain('24-Hour Pickup')
        ->toContain('Cancellation Policy')
        ->toContain('Cash payment is made at the shop counter')
        ->toContain('Custom-parts requests carry no online payment')
        ->toContain('today only')
        ->toContain('accept</strong> or <strong>decline')
        ->toContain('12% VAT');
});

test('while the GCash QR gate is closed the terms page does not describe an unavailable payment method', function () {
    config(['payments.gcash_qr.enabled' => false]);

    $html = $this->get('/terms')->assertOk()->getContent();

    expect($html)->not->toContain('Payment by GCash QR')
        ->and($html)->toContain('GCash QR payment is not available yet');
});

test('once the GCash QR gate is open the terms say the owner verifies payment and nothing is automatic', function () {
    config(['payments.gcash_qr.enabled' => true]);

    $html = $this->get('/terms')->assertOk()->getContent();

    expect($html)->toContain('Payment by GCash QR')
        ->toContain('is not confirmation of payment')
        ->toContain('not</em> reactivated')
        ->toContain('not automatically refunded');
});

test('the terms page makes no unapproved promises about auto-cancellation, capacity or refunds', function () {
    foreach ([false, true] as $gate) {
        config(['payments.gcash_qr.enabled' => $gate]);
        $text = strtolower(strip_tags($this->get('/terms')->getContent()));

        expect($text)
            ->not->toContain('automatically cancel')
            ->not->toContain('auto-cancel')
            ->not->toContain('30 minutes')
            ->not->toContain('fully refund')
            ->not->toContain('full refund')
            ->not->toContain('refund within')
            ->not->toContain('maximum of')
            ->not->toContain('guaranteed');
    }
});

test('the checkout terms modal and the terms page share one reservation text', function () {
    $partial = trim(view('shop.partials.terms-reservation')->render());

    expect($partial)->not->toBe('')
        ->and($this->get('/terms')->getContent())->toContain('6. Returns & Physical Refunds');
});
