<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$po = App\Models\Erp\ErpPurchaseOrder::where('po_no', 'PO-2026-00001')->first();
if ($po) {
    echo "PO: {$po->po_no}, Status: {$po->status}, Amount: {$po->total_po_amount_with_tax}\n";
    echo "Approvals:\n";
    foreach ($po->approvals()->orderBy('level')->get() as $appr) {
        $assigned = $appr->assignedUser?->name ?? ($appr->assignedRole?->name ?? '-');
        echo "Level: {$appr->level}, Status: {$appr->status}, Assigned To: {$assigned}\n";
    }
}
