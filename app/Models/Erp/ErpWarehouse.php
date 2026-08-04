<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;

class ErpWarehouse extends Model
{
    protected $table = 'erp_warehouses';

    protected $fillable = [
        'warehouse_code',
        'name',
        'type',
        'address',
        'phone',
        'fax',
        'last_stock_take_date',
        'work',
        'is_active',
        'latitude',
        'longitude',
        'capacity',
        'total_value',
        'remark',
    ];

    protected $casts = [
        'last_stock_take_date' => 'date',
        'is_active' => 'boolean',
        'capacity' => 'integer',
        'total_value' => 'decimal:2',
    ];
}
