<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ErpSubProject extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';

    protected $fillable = [
        'budget_parent_id',
        'sub_project_code',
        'name',
    ];

    public function budgetParent()
    {
        return $this->belongsTo(ErpBudgetParent::class, 'budget_parent_id');
    }

    public function workItems()
    {
        return $this->hasMany(ErpWorkItem::class, 'sub_project_id');
    }
}
