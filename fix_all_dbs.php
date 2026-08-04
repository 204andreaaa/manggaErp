<?php
$databases = ['mp3_master', 'mandau_db'];
foreach ($databases as $db) {
    try {
        $res = DB::table($db . '.request_form_items')
            ->where('rf_detail_no', 'like', 'RFIN-2026-07-00003-%')
            ->update(['status' => 'Completed']);
        echo "Updated $res rows in $db.request_form_items\n";
    } catch (\Exception $e) {
        echo "Table not found in $db\n";
    }

    try {
        $res = DB::table($db . '.erp_purchase_request_items')
            ->where('pr_detail_no', 'like', 'PRIN-2026-07-00003%')
            ->update(['status' => 'Completed']);
        echo "Updated $res rows in $db.erp_purchase_request_items\n";
    } catch (\Exception $e) {
        echo "Table not found in $db\n";
    }
}
