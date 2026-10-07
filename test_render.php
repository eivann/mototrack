<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

auth()->loginUsingId(1);
try {
    $html = view('admin.dashboard', [
        'stats' => ['totalProducts' => 1, 'todayRevenue' => 0, 'activeRepairs' => 0, 'lowStockCount' => 0],
        'chartLabels' => [],
        'chartData' => [],
        'recentSales' => collect(),
        'activeServiceJobs' => collect(),
        'pendingPreorders' => collect(),
        'recentPreorders' => collect(),
        'recentActivities' => collect(),
        'inventorySummary' => [],
        'pendingRefunds' => collect()
    ])->render();
    
    // Output all x-data blocks
    preg_match_all('/x-data="([^"]+)"/', $html, $matches);
    foreach ($matches[1] as $match) {
        echo "\n\n--- MATCH ---\n";
        echo html_entity_decode($match);
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
