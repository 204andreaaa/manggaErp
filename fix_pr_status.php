<?php
$poItem = \App\Models\Erp\ErpPurchaseOrderItem::whereHas('requestFormItem.purchaseRequestItems', function($q) {
    $q->where('pr_detail_no', 'PRIN-2026-07-00003');
})->first();
if ($poItem) {
    echo 'PO Item ID: ' . $poItem->id . PHP_EOL;
    $grItem = \App\Models\Erp\ErpGoodsReceiptItem::where('erp_purchase_order_item_id', $poItem->id)->first();
    if ($grItem) {
        $gr = $grItem->goodsReceipt;
        echo 'GR Status: ' . $gr->status . PHP_EOL;
        if ($gr->status === 'Draft' || $gr->status === 'Received') {
            $gr->status = 'Received';
            $gr->save();
            
            $rfItem = $grItem->requestFormItem;
            if ($rfItem) {
                $rfItem->update(['status' => 'Completed']);
                foreach ($rfItem->purchaseRequestItems as $prItem) {
                    $prItem->update(['status' => 'Completed']);
                }
            }
            echo 'Updated GR and PR Item to Completed' . PHP_EOL;
        }
    } else {
        echo 'No GR Item found' . PHP_EOL;
    }
} else {
    echo 'No PO Item found' . PHP_EOL;
}

