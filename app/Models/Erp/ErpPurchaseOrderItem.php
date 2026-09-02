<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;

class ErpPurchaseOrderItem extends Model
{
    protected $connection = 'tenant';
    protected $table = 'erp_purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'request_form_item_id',
        'qty',
        'unit_cost',
        'tax',
        'total_cost',
        'remarks',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(ErpPurchaseOrder::class, 'purchase_order_id');
    }

    public function requestFormItem()
    {
        return $this->belongsTo(RequestFormItem::class, 'request_form_item_id');
    }

    public function goodsReceiptItems()
    {
        return $this->hasMany(ErpGoodsReceiptItem::class, 'erp_purchase_order_item_id');
    }

    public function getPoDetailNoAttribute()
    {
        $dateStr = $this->created_at ? $this->created_at->format('Y-m') : now()->format('Y-m');
        return 'POIN-' . $dateStr . '-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    public function getReceivedQtyAttribute()
    {
        return (float) ErpGoodsReceiptItem::where('erp_purchase_order_item_id', $this->id)
            ->whereHas('goodsReceipt', function($q) {
                $q->where('status', 'Received');
            })
            ->sum('received_qty');
    }

    public function getRemainingQtyAttribute()
    {
        return max(0, (float) $this->qty - $this->received_qty);
    }
}

