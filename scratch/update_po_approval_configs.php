<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$febri = App\Models\User::where('username', 'febri')->orWhere('email', 'febri@local.com')->first();
$melvien = App\Models\User::where('username', 'melvien')->orWhere('email', 'melvien@local.com')->first();
$barry = App\Models\User::where('username', 'barry')->orWhere('email', 'barry@local.com')->first();

echo "Febri: " . ($febri ? "ID {$febri->id}" : 'not found') . "\n";
echo "Melvien: " . ($melvien ? "ID {$melvien->id}" : 'not found') . "\n";
echo "Barry: " . ($barry ? "ID {$barry->id}" : 'not found') . "\n";

// Update approval configs for purchase_order
App\Models\Erp\ErpApprovalConfig::where('record_type', 'purchase_order')->delete();

App\Models\Erp\ErpApprovalConfig::create([
    'record_type' => 'purchase_order',
    'level' => 1,
    'name' => 'Procurement Review / Approval',
    'user_id' => $febri?->id,
    'min_amount' => null,
    'max_amount' => null,
]);

App\Models\Erp\ErpApprovalConfig::create([
    'record_type' => 'purchase_order',
    'level' => 2,
    'name' => 'Finance Verification',
    'user_id' => $melvien?->id,
    'min_amount' => null,
    'max_amount' => null,
]);

App\Models\Erp\ErpApprovalConfig::create([
    'record_type' => 'purchase_order',
    'level' => 3,
    'name' => 'CEO Approval (High Value)',
    'user_id' => $barry?->id,
    'min_amount' => 1000000.01,
    'max_amount' => null,
]);

echo "Updated PO Approval Configs Successfully!\n";
$configs = App\Models\Erp\ErpApprovalConfig::where('record_type', 'purchase_order')->orderBy('level')->get();
foreach ($configs as $c) {
    echo "Level: {$c->level}, Name: {$c->name}, User: " . ($c->user?->name ?? '-') . ", Min: " . ($c->min_amount ?? '0') . ", Max: " . ($c->max_amount ?? '∞') . "\n";
}
