<?php
use Illuminate\Support\Facades\DB;
$pas = \App\Models\Erp\ErpPaymentAdvice::where('payment_closed', true)->get();
foreach($pas as $pa) {
    if($pa->purchaseOrder) {
        $pa->purchaseOrder->update(['payment_closed' => true]);
        echo "Updated PO {$pa->purchaseOrder->po_no}\n";
    }
}
echo "Done.\n";
