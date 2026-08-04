<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;

class ProductType extends Model
{
    protected $connection = 'tenant';
    protected $fillable = ['type_name', 'description'];
}
