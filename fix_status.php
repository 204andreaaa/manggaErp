<?php
$grs = \App\Models\Erp\ErpGoodsReceipt::where('status', 'Received')->get();
foreach ($grs as $gr) {
    foreach ($gr->items as $item) {
        if ($item->requestFormItem) {
            $item->requestFormItem->update(['status' => 'Completed']);
            if ($item->requestFormItem->purchaseRequestItem) {
                $item->requestFormItem->purchaseRequestItem->update(['status' => 'Completed']);
            }
        }
    }
}
echo 'Done updating old records';

