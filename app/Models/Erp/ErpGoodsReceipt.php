<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ErpGoodsReceipt extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'document_complete_date' => 'date',
        'status_receive_date' => 'date',
        'verification_timestamp' => 'datetime',
        'bypass_verification' => 'boolean',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(ErpPurchaseOrder::class, 'erp_purchase_order_id');
    }

    public function supplier()
    {
        return $this->belongsTo(ErpSupplier::class, 'supplier_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(ErpWarehouse::class, 'warehouse_id');
    }

    public function items()
    {
        return $this->hasMany(ErpGoodsReceiptItem::class, 'erp_goods_receipt_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
