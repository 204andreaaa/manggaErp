<?php

namespace Database\Seeders\Core;

use Illuminate\Database\Seeder;
use App\Models\Role;
use Database\Seeders\Launch\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /** Request Form access every role gets (also back-filled by a master migration). */
    public const RF_FOR_EVERYONE = ['request_forms.view', 'request_forms.create', 'request_forms.submit'];

    public function run(): void
    {
        // 1. Clean legacy roles if any
        Role::whereIn('slug', ['warehouse', 'sales'])->delete();

        // 2. Definisi Roles Resmi ERP
        $rolesData = [
            'superadmin' => [
                'name'        => 'Super Admin',
                'home_route'  => 'dashboard',
            ],
            'admin' => [
                'name'        => 'Admin',
                'home_route'  => 'dashboard',
            ],
            'admin_project' => [
                'name'        => 'Admin Project',
                'home_route'  => 'erp.work-items.index',
            ],
            'general_affair' => [
                'name'        => 'General Affair (GA)',
                'home_route'  => 'erp.goods-receipts.index',
            ],
            'procurement' => [
                'name'        => 'Procurement',
                'home_route'  => 'erp.purchase-orders.index',
            ],
            'logistik' => [
                'name'        => 'Logistik & Gudang',
                'home_route'  => 'erp.stocks.index',
            ],
            'finance' => [
                'name'        => 'Finance',
                'home_route'  => 'erp.payment-advices.index',
            ],
            'hrd' => [
                'name'        => 'Human Resource (HRD)',
                'home_route'  => 'erp.hr.employees.index',
            ],
            'ceo' => [
                'name'        => 'Chief Executive Officer (CEO)',
                'home_route'  => 'dashboard',
            ],
        ];

        $items   = collect(config('menu.items', []));
        $allKeys = $items->pluck('key')->filter()->values()->all();

        // Kumpulkan seluruh permission dari menu.php
        $allPermissions = [];
        foreach ($items as $item) {
            if (!empty($item['permissions'])) {
                foreach ($item['permissions'] as $p) {
                    $allPermissions[] = $p;
                }
            }
        }
        $allPermissions = array_values(array_unique($allPermissions));

        // Mapping menus & permissions per role
        $roleConfigs = [
            'superadmin' => [
                'menu_keys'   => $allKeys,
                'permissions' => $allPermissions,
            ],
            'admin' => [
                'menu_keys'   => $allKeys,
                'permissions' => $allPermissions,
            ],
        ];

        // Division roles and the CEO follow the standard matrix of the launch seeder.
        foreach (array_keys($rolesData) as $slug) {
            if ($standard = RolePermissionSeeder::for($slug)) {
                $roleConfigs[$slug] = $standard;
            }
        }

        // Every employee may raise a Request Form; only its maker (or an admin) edits/submits it.
        foreach ($roleConfigs as $slug => $conf) {
            $roleConfigs[$slug]['menu_keys'] = array_values(array_unique(array_merge($conf['menu_keys'], ['request_forms'])));
            $roleConfigs[$slug]['permissions'] = array_values(array_unique(array_merge($conf['permissions'], self::RF_FOR_EVERYONE)));
        }

        foreach ($rolesData as $slug => $data) {
            $conf = $roleConfigs[$slug] ?? ['menu_keys' => [], 'permissions' => []];
            Role::updateOrCreate(
                ['slug' => $slug],
                [
                    'name'        => $data['name'],
                    'home_route'  => $data['home_route'],
                    'menu_keys'   => $conf['menu_keys'],
                    'permissions' => $conf['permissions'],
                ]
            );
        }
    }
}
