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
        'top', 'remarks', 'due_date', 'status', 'invoice_amount', 'invoice_amount_usd', 'amount_paid', 'outstanding',
        'ppn_rate', 'ppn_amount', 'total_amount', 'pph_rate', 'pph_base', 'pph_amount', 'amount_due',
        'partial_payment_date', 'final_payment_date', 'created_by_id', 'updated_by_id',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'partial_payment_date' => 'date',
        'final_payment_date' => 'date',
        'invoice_amount' => 'decimal:2',
        'invoice_amount_usd' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'outstanding' => 'decimal:2',
        'ppn_rate' => 'decimal:2',
        'ppn_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'pph_rate' => 'decimal:2',
        'pph_base' => 'decimal:2',
        'pph_amount' => 'decimal:2',
        'amount_due' => 'decimal:2',
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

    /**
     * Taxes of an invoice worth $dpp (IDR, before tax):
     *  - PPN on top of the whole value: total = DPP + PPN;
     *  - PPh withheld by the customer on the services part only (pph_base, at most
     *    the DPP), counted as paid at once: amount_due = total - PPh.
     */
    public function taxesFor(float $dpp): array
    {
        $ppn = round($dpp * (float) $this->ppn_rate / 100, 2);
        $pph = round(min((float) $this->pph_base, $dpp) * (float) $this->pph_rate / 100, 2);

        return ['ppn_amount' => $ppn, 'total_amount' => $dpp + $ppn, 'pph_amount' => $pph, 'amount_due' => $dpp + $ppn - $pph];
    }

    /**
     * Recomputes amount, taxes, paid, outstanding, status and the payment dates from the
     * lines and payments. Payments and status work on the IDR amount; USD lines get their
     * own total (Salesforce's "Invoice Amount USD") until it is known how USD invoices are settled.
     */
    public function recalculate(): void
    {
        $amount = (float) $this->details()->where('currency', 'IDR')->sum('amount');
        $amountUsd = (float) $this->details()->where('currency', 'USD')->sum('amount');
        $paid = (float) $this->payments()->sum('amount');
        $taxes = $this->taxesFor($amount);
        $fullyPaid = $amount > 0 && $paid >= $taxes['amount_due'] - 0.005;

        $this->update($taxes + [
            'invoice_amount' => $amount,
            'invoice_amount_usd' => $amountUsd,
            'amount_paid' => $paid,
            'outstanding' => max(0, $taxes['amount_due'] - $paid),
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
