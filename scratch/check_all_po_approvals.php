<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pos = App\Models\Erp\ErpPurchaseOrder::all();
foreach ($pos as $po) {
    echo "ID: {$po->id}, PO No: {$po->po_no}, Status: {$po->status}, Amount: {$po->total_po_amount_with_tax}\n";
    foreach ($po->approvals()->orderBy('level')->get() as $appr) {
        $assigned = $appr->assignedUser?->name ?? ($appr->assignedRole?->name ?? '-');
        echo "   Level: {$appr->level}, Status: {$appr->status}, Assigned To: {$assigned}\n";
    }
}
