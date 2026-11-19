<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Role extends Model
{
    use HasFactory;

    protected $connection = 'master';

    protected $fillable = [
        'name',
        'slug',
        'home_route',
        'menu_keys',
        'permissions'
    ];

    protected $casts = [
        'menu_keys' => 'array',
        'permissions' => 'array',
    ];

    /**
     * Job roles that belong to a division. Code that checks a division slug
     * (hasRole('procurement'), route middleware, notifications) also lets these
     * through, so a Head of Procurement needs only one role. What each of them
     * may do still comes from its own permissions.
     */
    public const DIVISION_MEMBERS = [
        'procurement' => ['head_procurement'],
        'logistik' => ['head_logistic'],
        'finance' => ['head_finance'],
        'hrd' => ['head_hr'],
        'admin_project' => ['head_project', 'project_manager', 'project_coordinator', 'staff_project'],
    ];

    /** The given slugs plus the job roles of any division among them. */
    public static function withDivisionMembers(array $slugs): array
    {
        $all = $slugs;
        foreach ($slugs as $slug) {
            $all = array_merge($all, self::DIVISION_MEMBERS[$slug] ?? []);
        }

        return array_values(array_unique($all));
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user', 'role_id', 'user_id');
    }
}
