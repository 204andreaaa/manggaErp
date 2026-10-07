<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonthlyBudgetPlan extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'erp_monthly_budget_plans';

    protected $fillable = [
        'code',
        'annual_budget_plan_id',
        'year',
        'month',
        'unit',
        'qty',
        'revenue_plan',
        'total_revenue_plan',
        'total_direct_expense_plan',
        'total_indirect_expense_plan',
        'subtotal_expense_plan',
        'total_expense_plan',
        'total_actual_expense',
        'description',
        'approval_status',
    ];

    protected $casts = [
        'revenue_plan' => 'decimal:2',
        'total_revenue_plan' => 'decimal:2',
        'total_direct_expense_plan' => 'decimal:2',
        'total_indirect_expense_plan' => 'decimal:2',
        'subtotal_expense_plan' => 'decimal:2',
        'total_expense_plan' => 'decimal:2',
        'total_actual_expense' => 'decimal:2',
    ];

    public function annualBudgetPlan()
    {
        return $this->belongsTo(AnnualBudgetPlan::class, 'annual_budget_plan_id');
    }

    public function budgetPlanDetails()
    {
        return $this->hasMany(BudgetPlanDetail::class, 'monthly_budget_plan_id');
    }

    // Convenience accessors — these fields belong to the parent chain, not stored
    // redundantly here, so there's nothing that can ever drift out of sync with it.
    public function getBudgetParentAttribute()
    {
        return $this->annualBudgetPlan?->budgetParent;
    }

    public function getAreaAttribute()
    {
        return $this->annualBudgetPlan?->area;
    }

    public function getWorkTypeAttribute()
    {
        return $this->annualBudgetPlan?->work_type;
    }

    public function getWorkSubtypeAttribute()
    {
        return $this->annualBudgetPlan?->work_subtype;
    }
}
