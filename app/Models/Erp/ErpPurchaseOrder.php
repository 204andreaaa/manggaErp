<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ErpPurchaseOrder extends Model
{
    use \App\Models\Erp\Concerns\HasApprovalRounds;

    protected $connection = 'tenant';
    protected $table = 'erp_purchase_orders';

    protected $fillable = [
        'po_no',
        'request_form_id',
        'erp_warehouse_id',
        'supplier_id',
        'destination',
        'contact_person',
        'address',
        'bank_account',
        'date',
        'eta',
        'total_po_amount',
        'tax',
        'total_po_amount_with_tax',
        'balance_amount',
        'amount_paid',
        'payment_method',
        'status',
        'over',
        'description',
        'bypass_verification',
        'check_transfer_to',
        'elapsed_time',
        'payment_closed',
        'gr',
        'owner_id',
        'submitted_date',
        'approved_date',
        'rejected_date',
        'expense_material_equipment',
        'expense_material_subcon',
        'expense_personnel',
        'expense_transportation',
        'expense_utilities',
        'expense_office',
        'expense_other',
        'project',
        'invoice_to',
        'attention_to',
        'transfer_to',
        'other_instructions',
        'payment_terms',
        'signature',
        'verified_by_id',
        'verification_timestamp',
    ];

    protected $casts = [
        'date' => 'date',
        'eta' => 'date',
        'over' => 'boolean',
        'bypass_verification' => 'boolean',
        'payment_closed' => 'boolean',
        'gr' => 'boolean',
        'submitted_date' => 'datetime',
        'approved_date' => 'datetime',
        'rejected_date' => 'datetime',
        'verification_timestamp' => 'datetime',
        'expense_material_equipment' => 'boolean',
        'expense_material_subcon' => 'boolean',
        'expense_personnel' => 'boolean',
        'expense_transportation' => 'boolean',
        'expense_utilities' => 'boolean',
        'expense_office' => 'boolean',
        'expense_other' => 'boolean',
    ];

    public function requestForm()
    {
        return $this->belongsTo(RequestForm::class);
    }

    public function supplier()
    {
        return $this->belongsTo(ErpSupplier::class, 'supplier_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(ErpWarehouse::class, 'erp_warehouse_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by_id');
    }

    public function items()
    {
        return $this->hasMany(ErpPurchaseOrderItem::class, 'purchase_order_id');
    }

    public function goodsReceipts()
    {
        return $this->hasMany(ErpGoodsReceipt::class, 'erp_purchase_order_id');
    }

    public function paymentAdvices()
    {
        return $this->hasMany(ErpPaymentAdvice::class, 'erp_purchase_order_id');
    }

    public function approvals()
    {
        return $this->hasMany(ErpApproval::class, 'purchase_order_id');
    }

    public function notesAttachments()
    {
        return $this->morphMany(ErpNoteAttachment::class, 'notable');
    }

    public function getIsGrCompletedAttribute()
    {
        if ($this->items->isEmpty()) return false;
        foreach ($this->items as $item) {
            if ($item->remaining_qty > 0) {
                return false;
            }
        }
        return true;
    }

    public function getRemainingTotalQtyAttribute()
    {
        return (float) $this->items->sum(fn($i) => $i->remaining_qty);
    }

    /**
     * PO selesai (Completed) bila barang sudah diterima semua DAN pembayaran sudah lunas
     * (dibayar), urutannya bebas. Dipanggil dari GR receive dan dari termin Paid.
     */
    public function markCompletedIfDone(): bool
    {
        $this->refresh();
        $this->load('items');

        if ($this->status !== 'Approved') {
            return false;
        }

        if ($this->is_gr_completed && $this->payment_closed) {
            $this->update(['status' => 'Completed']);
            return true;
        }

        return false;
    }

    /**
     * Alasan PO tidak boleh di-cancel (null = boleh). PO tidak bisa di-cancel bila sudah
     * ada Goods Receipt, atau ada termin Payment Advice yang sudah diproses (diajukan,
     * disetujui, ditolak, atau dibayar).
     */
    public function cancelBlockReason(): ?string
    {
        if ($this->goodsReceipts()->exists()) {
            return 'PO tidak dapat di-cancel karena sudah ada Goods Receipt (penerimaan barang).';
        }

        $touchedTermin = ErpPaymentAdviceDetail::whereIn('erp_payment_advice_id', $this->paymentAdvices()->select('id'))
            ->where(function ($q) {
                $q->whereNotNull('approval_status')->where('approval_status', '!=', 'Draft')
                  ->orWhereNotNull('date_paid')
                  ->orWhereHas('approvals');
            })
            ->exists();

        if ($touchedTermin) {
            return 'PO tidak dapat di-cancel karena termin Payment Advice sudah diproses (diajukan, disetujui, ditolak, atau dibayar).';
        }

        return null;
    }
}

