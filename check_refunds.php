<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$refunds = \App\Models\RefundRequest::with('sale')->get();
foreach ($refunds as $refund) {
    echo "Refund ID: " . $refund->id . " Status: " . $refund->status . " Sale Status: " . ($refund->sale ? $refund->sale->status : 'none') . "\n";
}
echo "Total Refunds: " . $refunds->count() . "\n";
