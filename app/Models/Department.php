<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    /** Tabel departments berada di database master */
    protected $connection = 'master';
    protected $table = 'departments';

    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class, 'department', 'name');
    }
}
