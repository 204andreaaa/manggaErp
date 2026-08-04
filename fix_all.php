<?php
$grs = \App\Models\Erp\ErpGoodsReceipt::where('status', 'Received')->get();
foreach($grs as $gr) {
    foreach($gr->items as $item) {
        if ($item->requestFormItem) {
            $item->requestFormItem->update(['status' => 'Completed']);
            echo 'Updated RF Item: ' . $item->requestFormItem->rf_detail_no . PHP_EOL;
        }
    }
}

