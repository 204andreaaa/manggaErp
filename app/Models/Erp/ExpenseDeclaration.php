<?php

namespace App\Models\Erp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseDeclaration extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'erp_expense_declarations';

    protected $fillable = [
        'code',
        'record_type',
        'budget_plan_detail_id',
        'advance_request_id',
        'work_id',
        'site_name',
        'is_indirect',
        'po_number',
        'vehicle_info',
        'request_by_id',
        'request_date',
        'description',
        'amount_declaration',
        'currency',
        'exchange_rate',
        'converted_amount_idr',
        'rfin_ref',
        'remark_rf_line',
        'remark',
        'status',
    ];

    protected $casts = [
        'request_date' => 'date',
        'is_indirect' => 'boolean',
        'amount_declaration' => 'decimal:2',
        'exchange_rate' => 'decimal:8',
        'converted_amount_idr' => 'decimal:2',
    ];

    public function budgetPlanDetail()
    {
        return $this->belongsTo(BudgetPlanDetail::class, 'budget_plan_detail_id');
    }

    public function advanceRequest()
    {
        return $this->belongsTo(AdvanceRequest::class, 'advance_request_id');
    }

    public function work()
    {
        return $this->belongsTo(ErpWorkItem::class, 'work_id');
    }

    public function requestBy()
    {
        return $this->belongsTo(User::class, 'request_by_id');
    }

    public function approvals()
    {
        return $this->hasMany(ErpApproval::class, 'expense_declaration_id');
    }

    public function getBudgetParentAttribute()
    {
        return $this->budgetPlanDetail?->monthlyBudgetPlan?->budgetParent;
    }

    public function getExpenseTypeAttribute()
    {
        return $this->budgetPlanDetail?->expense_type;
    }
}
