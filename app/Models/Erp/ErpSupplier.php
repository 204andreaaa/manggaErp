<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;

class ErpSupplier extends Model
{
    protected $table = 'erp_suppliers';

    protected $fillable = [
        'supplier_code',
        'name',
        'parent_account',
        'classification',
        'industry',
        'products_provided',
        'services_provided',
        'category',
        'products',
        'payment_terms_id',
        'address',
        'phone',
        'fax',
        'website',
        'note',
        'bank_name',
        'bank_account',
    ];

    public function contacts()
    {
        return $this->hasMany(ErpSupplierContact::class, 'erp_supplier_id');
    }

    public function attachments()
    {
        return $this->hasMany(ErpSupplierAttachment::class, 'erp_supplier_id');
    }

    public function paymentTerm()
    {
        return $this->belongsTo(ErpPaymentTerm::class, 'payment_terms_id');
    }
}
