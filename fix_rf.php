<?php
$items = \App\Models\Erp\RequestFormItem::on('tenant')->where('rf_detail_no', 'like', 'RFIN-2026-07-%')->get();
foreach($items as $rfItem) {
    if (in_array($rfItem->rf_detail_no, ['RFIN-2026-07-00003-001', 'RFIN-2026-07-00003-002'])) {
        echo "Updating: " . $rfItem->rf_detail_no . "\n";
        $rfItem->update(['status' => 'Completed']);
    }
}
echo "Done\n";
