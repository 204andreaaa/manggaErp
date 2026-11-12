<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Every employee may raise a Request Form now that creating one is permission-checked:
 * each existing role gets the RF menu and request_forms.view/create/submit. Only the
 * RF's maker (or an admin) can edit or submit a given RF; that is enforced in code.
 */
return new class extends Migration
{
    protected $connection = 'master';

    private const PERMISSIONS = ['request_forms.view', 'request_forms.create', 'request_forms.submit'];

    public function up(): void
    {
        foreach (DB::connection('master')->table('roles')->get() as $role) {
            $permissions = json_decode($role->permissions ?? '[]', true) ?: [];
            $menuKeys = json_decode($role->menu_keys ?? '[]', true) ?: [];

            $newPermissions = array_values(array_unique(array_merge($permissions, self::PERMISSIONS)));
            $newMenuKeys = array_values(array_unique(array_merge($menuKeys, ['request_forms'])));

            if ($newPermissions !== $permissions || $newMenuKeys !== $menuKeys) {
                DB::connection('master')->table('roles')->where('id', $role->id)->update([
                    'permissions' => json_encode($newPermissions),
                    'menu_keys' => json_encode($newMenuKeys),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Not reversible: we can't tell which roles had these before.
    }
};
