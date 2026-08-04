<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;

class ErpStock extends Model
{
    protected $connection = 'tenant';
    protected $table = 'erp_stocks';

    protected $fillable = [
        'erp_product_id',
        'erp_warehouse_id',
        'qty_on_hand',
    ];

    protected $casts = [
        'qty_on_hand' => 'float',
    ];

    public function erpProduct()
    {
        return $this->belongsTo(ErpProduct::class, 'erp_product_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(ErpWarehouse::class, 'erp_warehouse_id');
    }
}
