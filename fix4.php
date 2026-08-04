<?php
$items = \App\Models\Erp\RequestFormItem::where('rf_detail_no', 'like', 'RFIN-2026-07-00003-%')->get();
foreach($items as $rfItem) {
    echo 'RF Item ID: ' . $rfItem->id . ' Status before: ' . $rfItem->status . PHP_EOL;
    $rfItem->update(['status' => 'Completed']);
    echo 'Updated Status: ' . $rfItem->status . PHP_EOL;
}

