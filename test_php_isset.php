<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$category = null;
$categories = ['Accessories', 'Brakes', 'Drivetrain', 'Electrical', 'Engine'];

foreach($categories as $cat) {
    echo "cat: $cat, is_selected: " . ((isset($category) && $category == $cat) ? 'yes' : 'no') . "\n";
}
