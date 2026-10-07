<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo json_encode(App\Models\Sale::whereHas('item', function($q) { $q->where('part_name', 'like', '%Brake Cable%'); })->get());
