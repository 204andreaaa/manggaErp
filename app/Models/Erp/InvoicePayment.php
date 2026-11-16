<?php

namespace App\Models\Erp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** A payment received from the customer against an invoice. */
class InvoicePayment extends Model
{
    protected $connection = 'tenant';
    protected $table = 'erp_invoice_payments';

    protected $fillable = ['invoice_id', 'payment_date', 'amount', 'method', 'reference', 'note', 'created_by_id'];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
