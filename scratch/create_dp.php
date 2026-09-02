<?php
$pad = new \App\Models\Erp\ErpPaymentAdviceDetail();
$pad->supplier_detail_no = 'SID-' . date('Y-m-d') . '-' . mt_rand(10000, 99999);
$pad->erp_payment_advice_id = 3;
$pad->erp_purchase_order_id = 6;
$pad->created_date_sid = now();
$pad->payment_amount = 13500000;
$pad->payment_amount_with_tax = 13500000;
$pad->payment_method = 'Bank Transfer';
$pad->payment_type = 'DP (10%)';
$pad->remark = 'Auto-generated termin schedule';
$pad->days_invoice_overdue = '< 30';
$pad->days_overdue = 1;
$pad->approval_status = 'Draft';
$pad->save();
echo "Created DP detail.";
