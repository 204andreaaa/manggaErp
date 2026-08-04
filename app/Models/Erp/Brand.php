<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $connection = 'tenant';
    protected $fillable = ['brand_name', 'description'];
}
