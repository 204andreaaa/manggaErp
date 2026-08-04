<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $connection = 'tenant';
    protected $fillable = ['code', 'name', 'symbol'];
}
