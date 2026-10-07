<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo htmlspecialchars((string) Illuminate\Support\Js::from(['foo' => 'bar']));
