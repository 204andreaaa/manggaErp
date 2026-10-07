<?php

namespace App\Models\Erp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ErpBudgetParent extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';

    protected $fillable = [
        'budget_code',
        'name',
        'kam_user_id',
        'total_budget',
        'remaining_budget',
        'status',
    ];

    public function subProjects()
    {
        return $this->hasMany(ErpSubProject::class, 'budget_parent_id');
    }

    public function annualBudgetPlans()
    {
        return $this->hasMany(AnnualBudgetPlan::class, 'budget_parent_id');
    }

    public function kam()
    {
        return $this->belongsTo(User::class, 'kam_user_id');
    }
}
