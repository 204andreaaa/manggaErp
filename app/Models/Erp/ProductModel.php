<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;

class ProductModel extends Model
{
    protected $connection = 'tenant';
    protected $fillable = ['model_name', 'description'];
}
