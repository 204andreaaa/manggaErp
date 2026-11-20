<?php

namespace App\Models\Erp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** One line of a customer invoice, normally per WID. */
class InvoiceDetail extends Model
{
    protected $connection = 'tenant';
    protected $table = 'erp_invoice_details';

    /** Currencies a line can be billed in (the invoice keeps an IDR and a USD total, like Salesforce). */
    public const CURRENCIES = ['IDR' => 'IDR - Indonesian Rupiah', 'USD' => 'USD - US Dollar'];

    /** Suggestions only; Terms stays free text until the real Salesforce picklist is known. */
    public const TERM_SUGGESTIONS = ['DP I', 'DP II', 'Termin I', 'Termin II', 'Final Payment'];

    protected $fillable = [
        'code', 'invoice_id', 'budget_parent_id', 'sub_project_id', 'work_item_id', 'project_code',
        'terms', 'currency', 'amount', 'po_date', 'received_doc_date', 'description', 'created_by_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'po_date' => 'date',
        'received_doc_date' => 'date',
    ];

    public function budgetParent()
    {
        return $this->belongsTo(ErpBudgetParent::class, 'budget_parent_id');
    }

    public function subProject()
    {
        return $this->belongsTo(ErpSubProject::class, 'sub_project_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function workItem()
    {
        return $this->belongsTo(ErpWorkItem::class, 'work_item_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
