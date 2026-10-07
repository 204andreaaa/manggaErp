<?php

namespace App\Models\Erp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdvanceRequest extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'erp_advance_requests';

    protected $fillable = [
        'code',
        'budget_plan_detail_id',
        'work_id',
        'budget_period',
        'advance_request_date',
        'request_by_id',
        'number_of_site',
        'amount_per_unit',
        'actual_amount',
        'total_amount_advance',
        'description',
        'status',
        'advance_payment_status',
        'payment_status',
        'payment_date',
        'paid_by_id',
    ];

    protected $casts = [
        'budget_period' => 'date',
        'advance_request_date' => 'date',
        'payment_date' => 'date',
        'amount_per_unit' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'total_amount_advance' => 'decimal:2',
    ];

    public function budgetPlanDetail()
    {
        return $this->belongsTo(BudgetPlanDetail::class, 'budget_plan_detail_id');
    }

    public function work()
    {
        return $this->belongsTo(ErpWorkItem::class, 'work_id');
    }

    public function requestBy()
    {
        return $this->belongsTo(User::class, 'request_by_id');
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by_id');
    }

    public function approvals()
    {
        return $this->hasMany(ErpApproval::class, 'advance_request_id');
    }

    public function expenseDeclarations()
    {
        return $this->hasMany(ExpenseDeclaration::class, 'advance_request_id');
    }

    // Inherited through budget_plan_detail -> monthly -> annual, never stored here.
    public function getBudgetParentAttribute()
    {
        return $this->budgetPlanDetail?->monthlyBudgetPlan?->budgetParent;
    }

    public function getAreaAttribute()
    {
        return $this->budgetPlanDetail?->monthlyBudgetPlan?->area;
    }

    public function getWorkTypeAttribute()
    {
        return $this->budgetPlanDetail?->monthlyBudgetPlan?->work_type;
    }

    public function getWorkSubtypeAttribute()
    {
        return $this->budgetPlanDetail?->monthlyBudgetPlan?->work_subtype;
    }

    public function getExpenseTypeAttribute()
    {
        return $this->budgetPlanDetail?->expense_type;
    }
}
