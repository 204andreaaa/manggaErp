<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Erp\ErpProduct;

$imageMap = [
    'PRD-001' => 'uploads/products/product_1785466284_rG943t.jpg', // Laptop
    'PRD-002' => 'uploads/products/product_1785467148_6V2r1B.jpg', // Mouse
    'PRD-003' => 'uploads/products/product_1785467180_9JCZWU.jpg', // Keyboard
    'PRD-004' => 'uploads/products/product_1785467211_ORihUI.jpg', // Monitor
    'PRD-005' => 'uploads/products/product_1785467114_M7mgc8.jpg', // USB-C Hub
    'PRD-006' => 'uploads/products/product_1785467057_0bCR5e.jpg', // Cisco Switch
    'PRD-007' => 'uploads/products/product_1785467057_0bCR5e.jpg', // Router
    'PRD-008' => 'uploads/products/product_1785467114_M7mgc8.jpg', // Cable
    'PRD-009' => 'uploads/products/product_1785467057_0bCR5e.jpg', // Server
    'PRD-010' => 'uploads/products/product_1785467211_ORihUI.jpg', // UPS
];

foreach ($imageMap as $code => $imagePath) {
    $product = ErpProduct::where('product_code', $code)->first();
    if ($product) {
        $product->update(['image' => $imagePath]);
        echo "Updated {$product->name} ({$code}) -> {$imagePath}\n";
    }
}

// Also update any other products without image using available files
$files = [
    'uploads/products/product_1785466284_rG943t.jpg',
    'uploads/products/product_1785467148_6V2r1B.jpg',
    'uploads/products/product_1785467180_9JCZWU.jpg',
    'uploads/products/product_1785467211_ORihUI.jpg',
    'uploads/products/product_1785467114_M7mgc8.jpg',
    'uploads/products/product_1785467057_0bCR5e.jpg',
];

$index = 0;
foreach (ErpProduct::whereNull('image')->orWhere('image', '')->get() as $p) {
    $img = $files[$index % count($files)];
    $p->update(['image' => $img]);
    echo "Assigned default image to ID {$p->id} ({$p->name}) -> {$img}\n";
    $index++;
}

echo "\nDone updating product images!\n";
