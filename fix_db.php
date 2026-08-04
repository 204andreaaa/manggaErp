<?php
$res = DB::connection('tenant')->table('request_form_items')
        ->where('rf_detail_no', 'like', 'RFIN-2026-07-00003-%')
        ->update(['status' => 'Completed']);

echo "Updated $res rows in request_form_items\n";

$res2 = DB::connection('tenant')->table('erp_purchase_request_items')
        ->where('pr_detail_no', 'like', 'PRIN-2026-07-%')
        ->update(['status' => 'Completed']);

echo "Updated $res2 rows in erp_purchase_request_items\n";
