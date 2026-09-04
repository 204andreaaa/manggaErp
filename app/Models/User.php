<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Role;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /** User login selalu dibaca dari database master. */
    protected $connection = 'master';

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'position',
        'signature_path',
        'password',
        'warehouse_id',
        'status',
        'role', // legacy
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /* ================== RELASI ================== */

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_user', 'user_id', 'project_id');
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id');
    }


    /* ================== HELPER ROLE ================== */

    public function primaryRole()
    {
        return $this->roles()
            ->select('slug', 'home_route')
            ->orderByRaw("FIELD(slug,'admin','warehouse','sales')")
            ->first();
    }

    public function hasRole(string|array $slugs): bool
    {
        $need = is_array($slugs) ? $slugs : [$slugs];

        return $this->roles()->whereIn('slug', $need)->exists()
            || in_array(($this->role ?? ''), $need, true);
    }

    /* ================== HELPER MENU ================== */

    public function allMenuKeys(): array
    {
        return $this->roles()
            ->pluck('menu_keys')
            ->filter()
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }

    public function canSeeMenu(string $key): bool
    {
        return in_array($key, $this->allMenuKeys(), true);
    }

    public function allowedMenuKeys(): array
    {
        $keys = $this->allMenuKeys();
        if ($this->hasRole(['superadmin', 'finance'])) {
            $keys[] = 'finance_transfers';
        }
        return $keys;
    }

    /* ================== HELPER PERMISSION ================== */

    public function hasPermission(string $permission): bool
    {
        if ($this->hasRole(['superadmin', 'admin'])) {
            return true;
        }

        foreach ($this->roles as $role) {
            if (in_array($permission, $role->permissions ?? [])) {
                return true;
            }
        }

        return false;
    }

    /* ================== SIGNATURE ================== */

    public function getSignatureUrlAttribute()
    {
        if (!$this->signature_path) {
            return null;
        }

        if (file_exists(public_path($this->signature_path))) {
            return asset($this->signature_path);
        }

        if (file_exists(storage_path('app/public/' . $this->signature_path))) {
            return asset('storage/' . $this->signature_path);
        }

        return null;
    }
}
