<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpGoodsReceiptItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function goodsReceipt()
    {
        return $this->belongsTo(ErpGoodsReceipt::class, 'erp_goods_receipt_id');
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(ErpPurchaseOrderItem::class, 'erp_purchase_order_item_id');
    }

    public function requestFormItem()
    {
        return $this->belongsTo(RequestFormItem::class, 'request_form_item_id');
    }
}
