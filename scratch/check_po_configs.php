<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$configs = App\Models\Erp\ErpApprovalConfig::where('record_type', 'purchase_order')->orderBy('level')->get();
echo "Current PO Approval Configs:\n";
foreach ($configs as $c) {
    echo "ID: {$c->id}, Level: {$c->level}, Name: {$c->name}, User: " . ($c->user?->name ?? '-') . ", Role: " . ($c->role?->name ?? '-') . ", Min: {$c->min_amount}, Max: {$c->max_amount}\n";
}
