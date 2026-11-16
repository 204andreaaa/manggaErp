<?php

namespace App\Models\Erp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** One line of a customer invoice, normally per WID. */
class InvoiceDetail extends Model
{
    protected $connection = 'tenant';
    protected $table = 'erp_invoice_details';

    protected $fillable = [
        'code', 'invoice_id', 'work_item_id', 'project_code', 'terms', 'amount',
        'po_date', 'received_doc_date', 'description', 'created_by_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'po_date' => 'date',
        'received_doc_date' => 'date',
    ];

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
