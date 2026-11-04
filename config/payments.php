<?php

/*
|--------------------------------------------------------------------------
| RD2-R2 — Customer payment methods
|--------------------------------------------------------------------------
| Cash at the counter stays the default and is always available. GCash QR is a MANUAL, owner-verified
| method for product reservations and pre-orders of catalogue items (never custom-parts requests).
|
| The supplied MotoTrack QR is a PROPOSED asset: it stays hidden and every GCash endpoint refuses
| until the owner confirms the receiving account is authorised and PAYMENTS_GCASH_QR_ENABLED=true is
| set deliberately in that environment. There is no gateway, callback or automatic approval, and no
| account identifier is read from, stored for, or logged about the QR.
*/
return [
    'gcash_qr' => [
        'enabled' => (bool) env('PAYMENTS_GCASH_QR_ENABLED', false),
        'label' => 'MotoTrack',
        // Tracked, unmodified asset; served only through the authenticated, gate-checked route.
        'qr_path' => resource_path('payments/gcash-qr.png'),
        'max_proof_kb' => 4096,
    ],
];
