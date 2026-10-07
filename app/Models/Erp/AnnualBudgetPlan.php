<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnnualBudgetPlan extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'erp_annual_budget_plans';

    protected $fillable = [
        'code',
        'budget_parent_id',
        'year',
        'area',
        'work_type',
        'work_subtype',
        'currency',
        'total_expense_plan',
        'total_actual_expense',
    ];

    protected $casts = [
        'total_expense_plan' => 'decimal:2',
        'total_actual_expense' => 'decimal:2',
    ];

    public function budgetParent()
    {
        return $this->belongsTo(ErpBudgetParent::class, 'budget_parent_id');
    }

    public function monthlyBudgetPlans()
    {
        return $this->hasMany(MonthlyBudgetPlan::class, 'annual_budget_plan_id');
    }
}
