<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ErpWorkItem extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';

    protected $fillable = [
        'sub_project_id',
        'wid_code',
        'name',
        'allocated_budget',
        'remaining_budget',
    ];

    public function subProject()
    {
        return $this->belongsTo(ErpSubProject::class, 'sub_project_id');
    }

    public function requestForms()
    {
        return $this->hasMany(RequestForm::class, 'work_item_id');
    }
}
