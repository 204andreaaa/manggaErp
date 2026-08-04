<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequestItem extends Model
{
    protected $connection = 'tenant';
    protected $table = 'erp_purchase_request_items';

    protected $fillable = [
        'purchase_request_id',
        'request_form_item_id',
        'required_qty',
        'pr_requested_qty',
    ];

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function requestFormItem()
    {
        return $this->belongsTo(RequestFormItem::class);
    }

    public function getPrDetailNoAttribute()
    {
        $dateStr = $this->created_at ? $this->created_at->format('Y-m') : now()->format('Y-m');
        return 'PRIN-' . $dateStr . '-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(ErpPurchaseOrderItem::class, 'request_form_item_id', 'request_form_item_id');
    }
}
