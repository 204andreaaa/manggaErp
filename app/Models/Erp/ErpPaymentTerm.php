<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;

class ErpPaymentTerm extends Model
{
    protected $table = 'erp_payment_terms';

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
