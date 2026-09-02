<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Role;

class ErpApproval extends Model
{
    protected $connection = 'tenant';
    
    protected $table = 'erp_approvals';

    protected $fillable = [
        'request_form_id',
        'purchase_order_id',
        'payment_advice_id',
        'payment_advice_detail_id',
        'level',
        'assigned_to_role_id',
        'assigned_to_user_id',
        'actual_approver_id',
        'status',
        'comments',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function requestForm()
    {
        return $this->belongsTo(RequestForm::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(ErpPurchaseOrder::class, 'purchase_order_id');
    }

    public function paymentAdvice()
    {
        return $this->belongsTo(ErpPaymentAdvice::class, 'payment_advice_id');
    }

    public function paymentAdviceDetail()
    {
        return $this->belongsTo(ErpPaymentAdviceDetail::class, 'payment_advice_detail_id');
    }

    public function assignedRole()
    {
        return $this->belongsTo(Role::class, 'assigned_to_role_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function actualApprover()
    {
        return $this->belongsTo(User::class, 'actual_approver_id');
    }
}
