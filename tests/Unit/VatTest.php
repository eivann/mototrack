<?php

use App\Support\Vat;

test('RD2-R2.4: VAT is extracted from a VAT-inclusive amount, never added on top', function () {
    // Owner's worked example: 1,120 inclusive -> 1,000 net + 120 VAT.
    $b = Vat::fromInclusive(1120.00);
    expect($b->gross)->toBe(1120.00)
        ->and($b->net)->toBe(1000.00)
        ->and($b->vat)->toBe(120.00)
        ->and($b->rate_percent)->toBe(12);
});

test('RD2-R2.4: net + VAT always reconciles exactly to the gross', function () {
    foreach ([0, 0.01, 1, 99.99, 100, 123.45, 350, 1250.50, 999999.99, 45.75] as $gross) {
        $b = Vat::fromInclusive($gross);
        expect(round($b->net + $b->vat, 2))->toBe(round((float) $gross, 2), "reconcile {$gross}");
        // VAT is a non-negative portion that never exceeds the gross.
        expect($b->vat)->toBeGreaterThanOrEqual(0.0)->toBeLessThanOrEqual((float) $gross);
    }
});

test('RD2-R2.4: rounding is to the centavo, half away from zero', function () {
    // 100.00 inclusive: 100*12/112 = 10.714... -> 10.71 VAT, 89.29 net.
    $b = Vat::fromInclusive(100.00);
    expect($b->vat)->toBe(10.71)->and($b->net)->toBe(89.29);
    // 0.01 inclusive: 0.01*12/112 = 0.00107 -> 0.00 VAT (whole amount is VATable).
    $b = Vat::fromInclusive(0.01);
    expect($b->vat)->toBe(0.00)->and($b->net)->toBe(0.01);
    // 0.05 inclusive: 5*12/112 = 0.535 cents -> rounds to 1 centavo VAT.
    $b = Vat::fromInclusive(0.05);
    expect($b->vat)->toBe(0.01)->and($b->net)->toBe(0.04);
});

test('RD2-R2.4: zero gross yields a zero breakdown', function () {
    $b = Vat::fromInclusive(0);
    expect($b->gross)->toBe(0.0)->and($b->net)->toBe(0.0)->and($b->vat)->toBe(0.0);
});

test('RD2-R2.4: string and integer inputs are accepted', function () {
    expect(Vat::fromInclusive('1120.00')->vat)->toBe(120.00);
    expect(Vat::fromInclusive(1120)->net)->toBe(1000.00);
});
