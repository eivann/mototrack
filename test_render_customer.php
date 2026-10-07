<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

auth()->loginUsingId(1);
try {
    $html = view('customer.dashboard', [
        'stats' => ['totalProducts' => 1, 'todayRevenue' => 0, 'activeRepairs' => 0, 'lowStockCount' => 0],
        'chartLabels' => [],
        'chartData' => [],
        'repairs' => collect(),
        'preorders' => collect(),
        'purchases' => collect(),
        'recentActivities' => collect()
    ])->render();
    
    // Output all x-data blocks
    preg_match_all('/x-data="([^"]+)"/', $html, $matches);
    foreach ($matches[1] as $match) {
        echo "\n\n--- MATCH ---\n";
        echo $match;
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
