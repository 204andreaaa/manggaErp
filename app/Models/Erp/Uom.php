<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;

class Uom extends Model
{
    protected $connection = 'tenant';
    protected $table = 'uoms';
    protected $fillable = ['uom_name', 'description'];
}
