<?php

namespace Database\Seeders\Launch;

use App\Models\Role;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Database\Seeder;

/**
 * The standard permissions and menus of every division and job role.
 *
 *  - a division's own work: full access (head) or everything except
 *    approve / reject / delete (staff);
 *  - modules of another division it needs to look at: ".view" only, and no menu,
 *    so the sidebar only shows the person's own division (the record still opens
 *    from a link on a PO, PA, kasbon, ...);
 *  - everything else: nothing.
 *
 * Approving a step is decided by Approval Config (the person named on the step),
 * not by these permissions. Running this seeder puts the roles below back to this
 * standard: changes made to them in Roles are overwritten. superadmin and admin are
 * not touched (they always pass every permission check).
 */
class RolePermissionSeeder extends Seeder
{
    private const PROJECT_HEAD = [
        'budget_parents.view', 'budget_parents.create', 'budget_parents.update', 'budget_parents.delete',
        'sub_projects.view', 'sub_projects.create', 'sub_projects.update', 'sub_projects.delete',
        'work_items.view', 'work_items.create', 'work_items.update', 'work_items.delete',
        'annual_budget_plans.view', 'annual_budget_plans.create',
        'advance_requests.view', 'advance_requests.create',
        'expense_declarations.view', 'expense_declarations.create',
        'request_forms.update',
        'purchase_orders.view', 'goods_receipts.view', 'invoices.view', 'products.view',
    ];

    private const PROJECT_MENUS = [
        'budget_parents', 'sub_projects', 'work_items', 'annual_budget_plans', 'advance_requests',
        'expense_declarations', 'purchase_orders', 'products',
    ];

    private const PROCUREMENT_HEAD = [
        'request_forms.update', 'request_forms.approve', 'request_forms.reject',
        'purchase_orders.view', 'purchase_orders.create', 'purchase_orders.update', 'purchase_orders.delete',
        'purchase_orders.verify', 'purchase_orders.submit', 'purchase_orders.approve', 'purchase_orders.reject', 'purchase_orders.print',
        'goods_receipts.view', 'goods_receipts.print',
        'suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete',
        'payment_terms.view', 'payment_terms.create', 'payment_terms.update', 'payment_terms.delete',
        'products.view', 'products.create', 'products.update', 'products.delete', 'products.export',
        'stocks.view',
    ];

    private const PROCUREMENT_MENUS = [
        'purchase_orders', 'suppliers', 'payment_terms', 'products', 'product_families', 'product_types',
        'brands', 'product_models', 'currencies', 'uoms',
    ];

    private const LOGISTIC_HEAD = [
        'request_forms.create_pr', 'request_forms.delete_pr',
        'purchase_orders.view', 'purchase_orders.print',
        'goods_receipts.view', 'goods_receipts.create', 'goods_receipts.update', 'goods_receipts.delete',
        'goods_receipts.verify', 'goods_receipts.print',
        'stocks.view', 'stocks.adjust',
        'warehouses.view', 'warehouses.create', 'warehouses.update', 'warehouses.delete',
        'suppliers.view', 'products.view',
    ];

    private const LOGISTIC_MENUS = ['purchase_orders', 'goods_receipts', 'stocks', 'warehouses', 'products', 'uoms'];

    private const GA = [
        'request_forms.update',
        'purchase_orders.view', 'purchase_orders.print',
        'goods_receipts.view', 'goods_receipts.create', 'goods_receipts.update', 'goods_receipts.verify', 'goods_receipts.print',
        'products.view',
        'vehicles.view', 'vehicles.create', 'vehicles.update', 'vehicles.delete',
    ];

    private const GA_MENUS = ['purchase_orders', 'goods_receipts', 'products', 'vehicles'];

    private const FINANCE_HEAD = [
        'purchase_orders.view', 'purchase_orders.print',
        'goods_receipts.view', 'goods_receipts.print',
        'suppliers.view', 'payment_terms.view',
        'payment_advices.view', 'payment_advices.update', 'payment_advices.delete',
        'payment_advice_details.view', 'payment_advice_details.update_invoice', 'payment_advice_details.submit',
        'payment_advice_details.approve', 'payment_advice_details.reject', 'payment_advice_details.mark_paid',
        'invoices.view', 'invoices.create', 'invoices.update', 'invoices.delete',
        'advance_requests.view', 'advance_requests.approve', 'advance_requests.pay',
        'expense_declarations.view', 'expense_declarations.approve',
        'annual_budget_plans.view', 'department_spending.view',
        'budget_parents.view', 'sub_projects.view', 'work_items.view',
    ];

    private const FINANCE_MENUS = [
        'purchase_orders', 'invoices', 'payment_advices', 'payment_advice_details', 'advance_requests',
        'expense_declarations', 'annual_budget_plans', 'department_spending',
    ];

    private const HR_HEAD = [
        'departments.view', 'departments.create', 'departments.update', 'departments.delete',
        'employees.view', 'employees.create', 'employees.update', 'employees.delete',
        'hr_attendances.view', 'hr_attendances.manage',
        'hr_payroll.view', 'hr_payroll.manage',
    ];

    private const HR_MENUS = ['departments', 'employees', 'hr_attendances', 'hr_payroll'];

    private const CEO = [
        'budget_parents.view', 'sub_projects.view', 'work_items.view',
        'annual_budget_plans.view', 'department_spending.view',
        'advance_requests.view', 'advance_requests.approve', 'expense_declarations.view', 'expense_declarations.approve',
        'request_forms.approve', 'request_forms.reject',
        'purchase_orders.view', 'purchase_orders.approve', 'purchase_orders.reject', 'purchase_orders.print',
        'goods_receipts.view', 'goods_receipts.print',
        'suppliers.view', 'payment_terms.view',
        'payment_advices.view', 'payment_advice_details.view', 'payment_advice_details.approve',
        'payment_advice_details.reject', 'payment_advice_details.mark_paid',
        'invoices.view', 'stocks.view', 'warehouses.view', 'products.view', 'vehicles.view',
        'departments.view', 'employees.view', 'approval_configs.view',
    ];

    private const CEO_MENUS = [
        'budget_parents', 'sub_projects', 'work_items', 'annual_budget_plans', 'department_spending',
        'advance_requests', 'expense_declarations', 'purchase_orders', 'goods_receipts', 'suppliers', 'payment_terms',
        'invoices', 'payment_advices', 'payment_advice_details', 'stocks', 'warehouses', 'products', 'vehicles',
        'departments', 'employees', 'approval_configs',
    ];

    /** What a staff member does not get compared to the head of the same division. */
    private const HEAD_ONLY_ACTIONS = ['approve', 'reject', 'delete', 'verify', 'delete_pr'];

    /** slug => [name, permissions, menus]; RF access for everyone is added to each. */
    public static function matrix(): array
    {
        $staff = fn (array $head) => self::staffOf($head);

        return [
            'head_project' => ['Head of Project', self::PROJECT_HEAD, self::PROJECT_MENUS],
            'admin_project' => ['Admin Project', self::PROJECT_HEAD, self::PROJECT_MENUS],
            'project_manager' => ['Project Manager', $staff(self::PROJECT_HEAD), self::PROJECT_MENUS],
            'project_coordinator' => ['Project Coordinator', $staff(self::PROJECT_HEAD), self::PROJECT_MENUS],
            'staff_project' => ['Staff Project', $staff(self::PROJECT_HEAD), self::PROJECT_MENUS],

            'head_procurement' => ['Head of Procurement', self::PROCUREMENT_HEAD, self::PROCUREMENT_MENUS],
            'procurement' => ['Procurement', $staff(self::PROCUREMENT_HEAD), self::PROCUREMENT_MENUS],

            'head_logistic' => ['Head of Logistic', self::LOGISTIC_HEAD, self::LOGISTIC_MENUS],
            'logistik' => ['Logistik & Gudang', $staff(self::LOGISTIC_HEAD), self::LOGISTIC_MENUS],

            'general_affair' => ['General Affair (GA)', self::GA, self::GA_MENUS],

            'head_finance' => ['Head of Finance', self::FINANCE_HEAD, self::FINANCE_MENUS],
            'finance' => ['Finance', $staff(self::FINANCE_HEAD), self::FINANCE_MENUS],

            'head_hr' => ['Head of HR', self::HR_HEAD, self::HR_MENUS],
            'hrd' => ['Human Resource (HRD)', $staff(self::HR_HEAD), self::HR_MENUS],

            'ceo' => ['Chief Executive Officer (CEO)', self::CEO, self::CEO_MENUS],
        ];
    }

    /** Final permission and menu lists of one role, RF access included. */
    public static function for(string $slug): ?array
    {
        $row = self::matrix()[$slug] ?? null;
        if (!$row) {
            return null;
        }

        return [
            'permissions' => array_values(array_unique(array_merge($row[1], RoleSeeder::RF_FOR_EVERYONE))),
            'menu_keys' => array_values(array_unique(array_merge($row[2], ['request_forms']))),
        ];
    }

    private static function staffOf(array $head): array
    {
        return array_values(array_filter($head, function (string $permission) {
            $action = substr($permission, strrpos($permission, '.') + 1);

            return !in_array($action, self::HEAD_ONLY_ACTIONS, true);
        }));
    }

    public function run(): void
    {
        foreach (self::matrix() as $slug => [$name]) {
            $role = Role::firstOrNew(['slug' => $slug]);
            $role->name ??= $name;
            $role->fill(self::for($slug));
            $role->save();
            $this->command?->line("izin {$slug}: ".count($role->permissions).' izin, '.count($role->menu_keys).' menu');
        }
    }
}
