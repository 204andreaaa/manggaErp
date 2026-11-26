<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * New vehicle module: General Affair gets full access, the CEO can look.
 * Other roles can be given it from the Role page.
 */
return new class extends Migration
{
    protected $connection = 'master';

    private const GRANTS = [
        'general_affair' => ['vehicles.view', 'vehicles.create', 'vehicles.update', 'vehicles.delete'],
        'ceo' => ['vehicles.view'],
    ];

    public function up(): void
    {
        foreach (self::GRANTS as $slug => $permissions) {
            $role = DB::connection('master')->table('roles')->where('slug', $slug)->first();
            if (!$role) {
                continue;
            }

            $current = json_decode($role->permissions ?? '[]', true) ?: [];
            $menuKeys = json_decode($role->menu_keys ?? '[]', true) ?: [];

            DB::connection('master')->table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode(array_values(array_unique(array_merge($current, $permissions)))),
                'menu_keys' => json_encode(array_values(array_unique(array_merge($menuKeys, ['vehicles'])))),
            ]);
        }
    }

    public function down(): void
    {
        // not reversible: other changes to these roles may have happened since
    }
};
