<?php
$grs = \App\Models\Erp\ErpGoodsReceipt::all();
foreach($grs as $gr) {
    echo 'GR ID: ' . $gr->id . ' Status: ' . $gr->status . ' PO ID: ' . $gr->erp_purchase_order_id . PHP_EOL;
    foreach($gr->items as $item) {
        echo '  Item PO_ITEM_ID: ' . $item->erp_purchase_order_item_id . ' RF_ITEM_ID: ' . $item->request_form_item_id . PHP_EOL;
    }
}

