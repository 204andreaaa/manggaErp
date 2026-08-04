<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ErpPaymentAdvice extends Model
{
    protected $connection = 'tenant';
    protected $table = 'erp_payment_advices';

    protected $fillable = [
        'supplier_invoice_no',
        'erp_purchase_order_id',
        'supplier_id',
        'invoice_no',
        'contact_person',
        'due_date',
        'total_invoice_amount',
        'total_invoice_amount_with_tax',
        'sum_payment_amount',
        'sum_payment_amount_with_tax',
        'outstanding',
        'status',
        'approval_status',
        'payment_closed',
        'owner_id',
    ];

    protected $casts = [
        'due_date' => 'date',
        'payment_closed' => 'boolean',
        'total_invoice_amount' => 'decimal:2',
        'total_invoice_amount_with_tax' => 'decimal:2',
        'sum_payment_amount' => 'decimal:2',
        'sum_payment_amount_with_tax' => 'decimal:2',
        'outstanding' => 'decimal:2',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(ErpPurchaseOrder::class, 'erp_purchase_order_id');
    }

    public function supplier()
    {
        return $this->belongsTo(ErpSupplier::class, 'supplier_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function details()
    {
        return $this->hasMany(ErpPaymentAdviceDetail::class, 'erp_payment_advice_id');
    }

    public function approvals()
    {
        return $this->hasMany(ErpApproval::class, 'payment_advice_id');
    }
}
