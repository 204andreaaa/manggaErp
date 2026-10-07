<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BudgetPlanDetail extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'erp_budget_plan_details';

    protected $fillable = [
        'code',
        'monthly_budget_plan_id',
        'expense_type',
        'is_indirect',
        'amount',
        'total_expense_plan',
        'actual_advance_request',
        'actual_expense_with_advance',
        'actual_expense_without_advance',
        'actual_expense_amount',
        'description',
    ];

    protected $casts = [
        'is_indirect' => 'boolean',
        'amount' => 'decimal:2',
        'total_expense_plan' => 'decimal:2',
        'actual_advance_request' => 'decimal:2',
        'actual_expense_with_advance' => 'decimal:2',
        'actual_expense_without_advance' => 'decimal:2',
        'actual_expense_amount' => 'decimal:2',
    ];

    public function monthlyBudgetPlan()
    {
        return $this->belongsTo(MonthlyBudgetPlan::class, 'monthly_budget_plan_id');
    }

    public function advanceRequests()
    {
        return $this->hasMany(AdvanceRequest::class, 'budget_plan_detail_id');
    }

    public function expenseDeclarations()
    {
        return $this->hasMany(ExpenseDeclaration::class, 'budget_plan_detail_id');
    }
}
