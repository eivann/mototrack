<?php

use App\Models\InventoryItem;

// Load Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$items = InventoryItem::whereNull('sku')->orWhere('sku', '')->get();

echo "Found " . $items->count() . " items without SKU.\n";

foreach ($items as $item) {
    $c = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $item->category), 0, 3));
    $b = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $item->brand), 0, 3));
    
    // Fallback if strings are too short or empty
    if (empty($c)) $c = 'PRT';
    if (empty($b)) $b = 'GEN';
    
    do {
        $r = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $sku = "$c-$b-$r";
        $exists = InventoryItem::where('sku', $sku)->exists();
    } while ($exists);
    
    $item->sku = $sku;
    $item->save();
    
    echo "Assigned $sku to {$item->part_name} ({$item->id})\n";
}

echo "Done!\n";
