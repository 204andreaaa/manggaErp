<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Erp\ErpProduct;

$products = ErpProduct::all();

echo "Total Products: " . $products->count() . "\n\n";

foreach ($products as $p) {
    echo "ID: {$p->id} | Code: {$p->product_code} | Name: {$p->name} | Image: " . ($p->image ?: 'NONE') . "\n";
}
