<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequest extends Model
{
    use SoftDeletes;

    protected $table = 'erp_purchase_requests';

    protected $fillable = [
        'pr_no',
        'request_form_id',
        'requestor',
        'pr_date',
        'status',
        'expense_material_equipment',
        'expense_material_subcon',
        'expense_transportation',
        'expense_personnel',
        'expense_office',
        'expense_other',
        'expense_utilities',
    ];

    protected $casts = [
        'pr_date' => 'date',
        'expense_material_equipment' => 'boolean',
        'expense_material_subcon' => 'boolean',
        'expense_transportation' => 'boolean',
        'expense_personnel' => 'boolean',
        'expense_office' => 'boolean',
        'expense_other' => 'boolean',
        'expense_utilities' => 'boolean',
    ];

    public function requestForm()
    {
        return $this->belongsTo(RequestForm::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }
}
