<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;

class ErpSupplierAttachment extends Model
{
    protected $table = 'erp_supplier_attachments';

    protected $fillable = [
        'erp_supplier_id',
        'type',
        'title',
        'file_path',
        'created_by',
    ];

    public function supplier()
    {
        return $this->belongsTo(ErpSupplier::class, 'erp_supplier_id');
    }
}
