<?php
// Fix existing PA outstanding balances
$pas = \App\Models\Erp\ErpPaymentAdvice::where('outstanding', 0)->where('status', 'Draft')->get();
foreach ($pas as $pa) {
    $pa->outstanding = $pa->total_invoice_amount_with_tax;
    $pa->save();
}
echo "Fixed " . count($pas) . " records.\n";
