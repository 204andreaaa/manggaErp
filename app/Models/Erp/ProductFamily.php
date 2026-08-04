<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;

class ProductFamily extends Model
{
    protected $connection = 'tenant';
    protected $fillable = ['family_name', 'description'];
}
