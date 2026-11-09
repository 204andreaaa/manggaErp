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

    /** Department code (master seed) guessed from a role, first match wins. */
    public const ROLE_DEPARTMENT_CODES = [
        'PROJ' => ['admin_project', 'project_manager', 'site_engineer'],
        'PROC' => ['procurement'],
        'GA' => ['general_affair', 'ga'],
        'LOG' => ['logistik', 'warehouse'],
        'FIN' => ['finance', 'accounting'],
        'HRD' => ['hrd'],
        'EXEC' => ['ceo', 'superadmin', 'admin'],
    ];

    /** The active department the user's roles point to, or null when none does. */
    public static function guessFromRoles(User $user): ?self
    {
        foreach (self::ROLE_DEPARTMENT_CODES as $code => $roles) {
            if ($user->hasRole($roles)) {
                return static::where('code', $code)->where('status', 'active')->first();
            }
        }

        return null;
    }

    /** The active department on the user's employee record, or null when that data is missing. */
    public static function forUser(?User $user): ?self
    {
        $name = $user?->employee?->department;

        return $name ? static::where('name', $name)->where('status', 'active')->first() : null;
    }
}
