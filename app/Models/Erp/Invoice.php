<?php

namespace App\Models\Erp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Customer invoice (money coming in). The amount is the sum of its lines per WID,
 * the status follows the payments received: New -> Partial Paid -> Paid.
 */
class Invoice extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'erp_invoices';

    public const STATUSES = ['New', 'Partial Paid', 'Paid'];
    public const PAYMENT_METHODS = ['Bank Transfer', 'Cash', 'Giro / Cek', 'Lainnya'];

    protected $fillable = [
        'code', 'invoice_number', 'invoice_date', 'company_id', 'company_name', 'customer_name',
        'top', 'remarks', 'due_date', 'status', 'invoice_amount', 'amount_paid', 'outstanding',
        'partial_payment_date', 'final_payment_date', 'created_by_id', 'updated_by_id',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'partial_payment_date' => 'date',
        'final_payment_date' => 'date',
        'invoice_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'outstanding' => 'decimal:2',
    ];

    public function details()
    {
        return $this->hasMany(InvoiceDetail::class, 'invoice_id');
    }

    public function payments()
    {
        return $this->hasMany(InvoicePayment::class, 'invoice_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /** Recomputes amount, paid, outstanding, status and the payment dates from the lines and payments. */
    public function recalculate(): void
    {
        $amount = (float) $this->details()->sum('amount');
        $paid = (float) $this->payments()->sum('amount');
        $fullyPaid = $amount > 0 && $paid >= $amount - 0.005;

        $this->update([
            'invoice_amount' => $amount,
            'amount_paid' => $paid,
            'outstanding' => max(0, $amount - $paid),
            'status' => $fullyPaid ? 'Paid' : ($paid > 0 ? 'Partial Paid' : 'New'),
            'partial_payment_date' => $paid > 0 ? $this->payments()->min('payment_date') : null,
            'final_payment_date' => $fullyPaid ? $this->payments()->max('payment_date') : null,
        ]);
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'Paid' && $this->due_date && $this->due_date->isPast();
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'Paid' => 'success',
            'Partial Paid' => 'warning',
            default => 'info',
        };
    }
}
