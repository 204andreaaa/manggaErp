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

    /** The active department on the user's employee record, or null when that data is missing. */
    public static function forUser(?User $user): ?self
    {
        $name = $user?->employee?->department;

        return $name ? static::where('name', $name)->where('status', 'active')->first() : null;
    }
}
