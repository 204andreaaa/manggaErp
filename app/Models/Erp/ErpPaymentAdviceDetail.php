<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;

class ErpPaymentAdviceDetail extends Model
{
    protected $connection = 'tenant';
    protected $table = 'erp_payment_advice_details';

    protected $fillable = [
        'supplier_detail_no',
        'erp_payment_advice_id',
        'erp_purchase_order_id',
        'erp_goods_receipt_id',
        'gr_date',
        'created_date_sid',
        'approved_date',
        'date_paid',
        'invoice_no',
        'invoice_attachment',
        'payment_receipt',
        'payment_amount',
        'payment_amount_with_tax',
        'payment_method',
        'payment_type',
        'remark',
        'days_invoice_overdue',
        'days_overdue',
        'approval_status',
    ];

    protected $casts = [
        'gr_date' => 'date',
        'created_date_sid' => 'date',
        'approved_date' => 'date',
        'date_paid' => 'date',
        'payment_amount' => 'decimal:2',
        'payment_amount_with_tax' => 'decimal:2',
    ];

    public function paymentAdvice()
    {
        return $this->belongsTo(ErpPaymentAdvice::class, 'erp_payment_advice_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(ErpPurchaseOrder::class, 'erp_purchase_order_id');
    }

    public function goodsReceipt()
    {
        return $this->belongsTo(ErpGoodsReceipt::class, 'erp_goods_receipt_id');
    }

    public function approvals()
    {
        return $this->hasMany(ErpApproval::class, 'payment_advice_detail_id');
    }

    public function previousUnapprovedDetail()
    {
        $allDetails = self::where('erp_payment_advice_id', $this->erp_payment_advice_id)
            ->orderBy('id', 'asc')
            ->get();

        foreach ($allDetails as $prev) {
            if ($prev->id === $this->id) {
                return null;
            }
            if ($prev->approval_status !== 'Approved') {
                return $prev;
            }
        }

        return null;
    }
}

