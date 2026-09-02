<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ErpBudgetParent extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';

    protected $fillable = [
        'budget_code',
        'name',
        'total_budget',
        'remaining_budget',
        'status',
    ];

    public function subProjects()
    {
        return $this->hasMany(ErpSubProject::class, 'budget_parent_id');
    }
}
