<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RequestForm extends Model
{
    use \App\Models\Erp\Concerns\HasApprovalRounds;

    protected $connection = 'tenant';
    use SoftDeletes;

    protected $fillable = [
        'created_by_id',
        'rf_no',
        'record_type',
        'project_code',
        'department_id',
        'department_name',
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
        'is_project',
        'work_item_id',
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
        'is_project' => 'boolean',
    ];

    public function workItem()
    {
        return $this->belongsTo(ErpWorkItem::class, 'work_item_id');
    }

    /** Non-project RFs only; departments live in the master database. */
    public function department()
    {
        return $this->belongsTo(\App\Models\Department::class, 'department_id');
    }

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

    /** Only the person who made the RF (or an admin) edits and submits it. */
    public function isManageableBy(?\App\Models\User $user): bool
    {
        return $user !== null
            && ((int) $this->created_by_id === (int) $user->id || $user->hasRole(['admin', 'superadmin']));
    }

    /**
     * Why this RF may not be deleted (null = it may). Once a PO exists the RF is part of
     * the purchase record, so every PO has to be cancelled first; cancelling is what
     * returns the WID budget and is itself refused after goods receipt or payment.
     */
    public function deleteBlockReason(): ?string
    {
        $active = $this->purchaseOrders()->where('status', '!=', 'Cancelled')->pluck('po_no');

        return $active->isEmpty() ? null
            : 'RF tidak dapat dihapus karena sudah ada PO ('.$active->join(', ').'). Cancel PO-nya terlebih dahulu.';
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
