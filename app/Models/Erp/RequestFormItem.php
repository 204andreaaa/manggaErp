<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;

class RequestFormItem extends Model
{
    protected $connection = 'tenant';
    protected $fillable = [
        'request_form_id',
        'rf_detail_no',
        'wid',
        'product_id_text',
        'product_name',
        'model',
        'product_description',
        'currency',
        'original_total_cost',
        'actual_cost',
        'remark',
        'qty',
        'qty_fulfilled',
        'date_required',
        'pic',
        'within_budget',
        'unit_cost',
        'status',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'qty_fulfilled' => 'decimal:2',
        'date_required' => 'date',
        'within_budget' => 'boolean',
        'original_total_cost' => 'integer',
        'actual_cost' => 'integer',
        'unit_cost' => 'integer',
    ];

    public function requestForm()
    {
        return $this->belongsTo(RequestForm::class);
    }

    public function purchaseRequestItems()
    {
        return $this->hasMany(PurchaseRequestItem::class, 'request_form_item_id');
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(ErpPurchaseOrderItem::class, 'request_form_item_id');
    }

    public function erpProduct()
    {
        return $this->belongsTo(ErpProduct::class, 'product_id_text', 'product_code');
    }
}
