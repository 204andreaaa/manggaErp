<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RequestForm extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'rf_no',
        'record_type',
        'project_code',
        'requestor',
        'owner',
        'priority',
        'rf_type',
        'rf_internal_do',
        'remark',
        'long_remark',
        'recommend_supplier',
        'rf_date',
        'status',
        'total_amount',
        'template_name',
        'expense_material_equipment',
        'expense_material_subcon',
        'expense_transportation',
        'expense_personnel',
        'expense_office',
        'expense_other',
        'expense_utilities',
        'rf_for',
    ];

    protected $casts = [
        'rf_internal_do' => 'boolean',
        'rf_date' => 'date',
        'total_amount' => 'integer',
        'expense_material_equipment' => 'boolean',
        'expense_material_subcon' => 'boolean',
        'expense_transportation' => 'boolean',
        'expense_personnel' => 'boolean',
        'expense_office' => 'boolean',
        'expense_other' => 'boolean',
        'expense_utilities' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(RequestFormItem::class);
    }

    public function purchaseRequests()
    {
        return $this->hasMany(PurchaseRequest::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(ErpPurchaseOrder::class, 'request_form_id');
    }

    public function approvals()
    {
        return $this->hasMany(ErpApproval::class);
    }

    public function notesAttachments()
    {
        return $this->morphMany(ErpNoteAttachment::class, 'notable');
    }

    public function getRecordTypeLabelAttribute(): string
    {
        return $this->record_type === 'project' ? 'Project Based' : 'Non Project Based';
    }
}
