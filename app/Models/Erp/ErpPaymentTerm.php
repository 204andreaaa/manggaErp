<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;

class ErpPaymentTerm extends Model
{
    protected $connection = 'tenant';
    protected $table = 'erp_payment_terms';

    protected $fillable = [
        'name',
        'is_active',
        'term_schedule',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'term_schedule' => 'array',
    ];
}
