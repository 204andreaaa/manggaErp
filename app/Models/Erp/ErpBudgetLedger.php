<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;

class ErpBudgetLedger extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'budget_parent_id',
        'work_item_id',
        'po_id',
        'type',
        'amount',
        'description',
    ];

    public function budgetParent()
    {
        return $this->belongsTo(ErpBudgetParent::class, 'budget_parent_id');
    }

    public function workItem()
    {
        return $this->belongsTo(ErpWorkItem::class, 'work_item_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(ErpPurchaseOrder::class, 'po_id');
    }
}
