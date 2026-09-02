<?php
// Group fragmented PAs back into single PA
$pos = \App\Models\Erp\ErpPurchaseOrder::all();
$fixedCount = 0;

foreach ($pos as $po) {
    // Check if PO has multiple draft PAs with a termin_name
    $pas = \App\Models\Erp\ErpPaymentAdvice::where('erp_purchase_order_id', $po->id)
        ->orderBy('created_at', 'asc')
        ->get();
        
    if ($pas->count() > 1 && $pas->whereNotNull('termin_name')->count() > 0) {
        echo "Fixing PO: " . $po->po_no . "\n";
        
        // Use the first PA as the master header
        $masterPa = $pas->first();
        $masterPa->total_invoice_amount = $po->total_po_amount_with_tax;
        $masterPa->total_invoice_amount_with_tax = $po->total_po_amount_with_tax;
        $masterPa->outstanding = $po->total_po_amount_with_tax;
        $masterPa->termin_name = null;
        $masterPa->save();
        
        foreach ($pas as $idx => $pa) {
            // Create detail from termin_name
            if ($pa->termin_name) {
                $pad = new \App\Models\Erp\ErpPaymentAdviceDetail();
                $pad->supplier_detail_no = 'SID-' . date('Y-m-d') . '-' . mt_rand(10000, 99999);
                $pad->erp_payment_advice_id = $masterPa->id;
                $pad->erp_purchase_order_id = $po->id;
                $pad->erp_goods_receipt_id = null;
                $pad->created_date_sid = now();
                $pad->payment_amount = $pa->total_invoice_amount_with_tax;
                $pad->payment_amount_with_tax = $pa->total_invoice_amount_with_tax;
                $pad->payment_method = 'Bank Transfer';
                $pad->payment_type = $pa->termin_name;
                $pad->remark = 'Auto-generated termin schedule';
                $pad->days_invoice_overdue = '< 30';
                $pad->days_overdue = 1;
                $pad->approval_status = 'Draft';
                $pad->save();
            }
            
            // Delete the other PAs (except master)
            if ($pa->id !== $masterPa->id) {
                $pa->delete();
            }
        }
        $fixedCount++;
    }
}
echo "Fixed {$fixedCount} POs.\n";
