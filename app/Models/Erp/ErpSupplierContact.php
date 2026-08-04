<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;

class ErpSupplierContact extends Model
{
    protected $table = 'erp_supplier_contacts';

    protected $fillable = [
        'erp_supplier_id',
        'contact_name',
        'title',
        'email',
        'phone',
    ];

    public function supplier()
    {
        return $this->belongsTo(ErpSupplier::class, 'erp_supplier_id');
    }
}
