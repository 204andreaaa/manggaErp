<?php

namespace Database\Seeders\Core;

use Illuminate\Database\Seeder;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
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
            'admin_project' => [
                'menu_keys'   => ['budget_parents', 'sub_projects', 'work_items', 'request_forms', 'products'],
                'permissions' => [
                    'budget_parents.view', 'budget_parents.create', 'budget_parents.update', 'budget_parents.delete',
                    'sub_projects.view', 'sub_projects.create', 'sub_projects.update', 'sub_projects.delete',
                    'work_items.view', 'work_items.create', 'work_items.update', 'work_items.delete',
                    'request_forms.view', 'request_forms.create', 'request_forms.update', 'request_forms.submit',
                    'products.view'
                ],
            ],
            'general_affair' => [
                'menu_keys'   => ['request_forms', 'goods_receipts', 'products'],
                'permissions' => [
                    'request_forms.view', 'request_forms.create', 'request_forms.update', 'request_forms.submit',
                    'goods_receipts.view', 'goods_receipts.create', 'goods_receipts.update', 'goods_receipts.verify', 'goods_receipts.print',
                    'products.view'
                ],
            ],
            'procurement' => [
                'menu_keys'   => ['request_forms', 'purchase_orders', 'goods_receipts', 'suppliers', 'payment_terms', 'products', 'product_families', 'product_types', 'brands', 'product_models', 'currencies', 'uoms'],
                'permissions' => [
                    'request_forms.view', 'request_forms.update', 'request_forms.approve', 'request_forms.reject',
                    'purchase_orders.view', 'purchase_orders.create', 'purchase_orders.update', 'purchase_orders.delete', 'purchase_orders.verify', 'purchase_orders.submit', 'purchase_orders.approve', 'purchase_orders.reject', 'purchase_orders.print',
                    'goods_receipts.view', 'goods_receipts.print',
                    'suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete',
                    'payment_terms.view', 'payment_terms.create', 'payment_terms.update', 'payment_terms.delete',
                    'products.view', 'products.create', 'products.update', 'products.export',
                    'product_families.view', 'product_types.view', 'brands.view', 'product_models.view', 'currencies.view', 'uoms.view'
                ],
            ],
            'logistik' => [
                'menu_keys'   => ['request_forms', 'goods_receipts', 'stocks', 'warehouses', 'products', 'uoms'],
                'permissions' => [
                    'request_forms.view', 'request_forms.create', 'request_forms.submit',
                    'goods_receipts.view', 'goods_receipts.create', 'goods_receipts.update', 'goods_receipts.verify', 'goods_receipts.print',
                    'stocks.view', 'stocks.adjust',
                    'warehouses.view', 'warehouses.create', 'warehouses.update', 'warehouses.delete',
                    'products.view', 'uoms.view'
                ],
            ],
            'finance' => [
                'menu_keys'   => ['purchase_orders', 'goods_receipts', 'payment_advices', 'payment_advice_details', 'suppliers', 'payment_terms'],
                'permissions' => [
                    'purchase_orders.view', 'purchase_orders.print',
                    'goods_receipts.view', 'goods_receipts.print',
                    'payment_advices.view', 'payment_advices.update',
                    'payment_advice_details.view', 'payment_advice_details.update_invoice', 'payment_advice_details.submit', 'payment_advice_details.approve', 'payment_advice_details.reject', 'payment_advice_details.mark_paid',
                    'suppliers.view', 'payment_terms.view'
                ],
            ],
            'hrd' => [
                'menu_keys'   => ['employees', 'hr_attendances', 'hr_payroll'],
                'permissions' => [
                    'employees.view', 'employees.create', 'employees.update', 'employees.delete',
                    'hr_attendances.view', 'hr_attendances.manage',
                    'hr_payroll.view', 'hr_payroll.manage',
                ],
            ],
            'ceo' => [
                'menu_keys'   => ['budget_parents', 'sub_projects', 'work_items', 'request_forms', 'purchase_orders', 'goods_receipts', 'payment_advices', 'payment_advice_details', 'stocks', 'products', 'approval_configs'],
                'permissions' => [
                    'budget_parents.view', 'sub_projects.view', 'work_items.view',
                    'request_forms.view', 'request_forms.approve', 'request_forms.reject',
                    'purchase_orders.view', 'purchase_orders.approve', 'purchase_orders.reject', 'purchase_orders.print',
                    'goods_receipts.view', 'goods_receipts.print',
                    'payment_advices.view', 'payment_advice_details.view', 'payment_advice_details.approve', 'payment_advice_details.reject', 'payment_advice_details.mark_paid',
                    'stocks.view', 'products.view', 'approval_configs.view'
                ],
            ],
        ];

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
