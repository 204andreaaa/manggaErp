<?php

namespace Database\Seeders\Erp;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Role;
use App\Models\Erp\ErpApprovalConfig;
use App\Models\Erp\Uom;
use App\Models\Erp\ProductFamily;
use App\Models\Erp\ProductType;
use App\Models\Erp\Brand;
use App\Models\Erp\ProductModel;
use App\Models\Erp\Currency;
use App\Models\Erp\ErpSupplier;
use App\Models\Erp\ErpWarehouse;
use App\Models\Erp\ErpPaymentTerm;

class ErpSetupSeeder extends Seeder
{
    public function run(): void
    {
        /* =======================================================
           1. ROLES
        ======================================================= */
        $newRoles = [
            'finance'     => ['name' => 'Finance'],
            'logistik'    => ['name' => 'Logistik'],
            'admin'       => ['name' => 'Admin'],
            'superadmin'  => ['name' => 'Super Admin'],
            'procurement' => ['name' => 'Procurement'],
            'ceo'         => ['name' => 'CEO'],
        ];

        foreach ($newRoles as $slug => $data) {
            Role::updateOrCreate(
                ['slug' => $slug],
                ['name' => $data['name']]
            );
        }
        $roles = Role::pluck('id', 'slug');

        /* =======================================================
           2. USERS (Sesuai Screenshot)
        ======================================================= */
        $usersData = [
            ['email' => 'admin@local', 'username' => 'admin', 'name' => 'Super Admin', 'phone' => '081200000001', 'role' => 'superadmin', 'position' => 'Super Admin'],
            ['email' => 'andrea@test.com', 'username' => 'andrea', 'name' => 'andrea', 'phone' => null, 'role' => 'admin', 'position' => 'Admin'],
            ['email' => 'melvien@example.com', 'username' => 'melvien', 'name' => 'Melvien Welang', 'phone' => null, 'role' => 'finance', 'position' => 'Finance'],
            ['email' => 'nikmal@example.com', 'username' => 'nikmal', 'name' => 'Nikmal Hadi', 'phone' => null, 'role' => 'logistik', 'position' => 'Logistik'],
            ['email' => 'budi@example.com', 'username' => 'budi', 'name' => 'Budi Atasan', 'phone' => null, 'role' => null, 'position' => 'Manager'],
            ['email' => 'siti@example.com', 'username' => 'siti', 'name' => 'Siti Staff', 'phone' => null, 'role' => null, 'position' => 'Staff'],
            ['email' => 'febri@local.com', 'username' => 'febri', 'name' => 'Febri Saputra', 'phone' => null, 'role' => 'procurement', 'position' => 'Procurement'],
            ['email' => 'barry@local.com', 'username' => 'barry', 'name' => 'Barry Japadarmawan', 'phone' => null, 'role' => 'ceo', 'position' => 'CEO'],
        ];

        foreach ($usersData as $ud) {
            $u = User::updateOrCreate(
                ['email' => $ud['email']],
                [
                    'name'     => $ud['name'],
                    'username' => $ud['username'],
                    'phone'    => $ud['phone'],
                    'password' => Hash::make('password123'), // Default password
                    'position' => $ud['position'],
                    'status'   => 'active'
                ]
            );

            if ($ud['role'] && isset($roles[$ud['role']])) {
                $u->roles()->sync([$roles[$ud['role']]]);
            }
        }

        /* =======================================================
           3. APPROVAL CONFIGURATION (Sesuai Screenshot)
        ======================================================= */
        $u_andrea = User::where('username', 'andrea')->first()?->id;
        $u_nikmal = User::where('username', 'nikmal')->first()?->id;
        $u_melvien = User::where('username', 'melvien')->first()?->id;
        $u_admin = User::where('username', 'admin')->first()?->id;
        $u_febri = User::where('username', 'febri')->first()?->id;
        $u_barry = User::where('username', 'barry')->first()?->id;

        ErpApprovalConfig::truncate();

        $configs = [
            // Project RF Approval
            ['record_type' => 'project', 'level' => 1, 'name' => 'KAM', 'user_id' => $u_andrea],

            // Non-Project RF Approval
            ['record_type' => 'non_project', 'level' => 1, 'name' => 'KAM', 'user_id' => $u_andrea],
            ['record_type' => 'non_project', 'level' => 2, 'name' => 'Logistik', 'user_id' => $u_nikmal],
            ['record_type' => 'non_project', 'level' => 3, 'name' => 'Finance', 'user_id' => $u_melvien],

            // PO Approval (<= 1M)
            ['record_type' => 'purchase_order_low', 'level' => 1, 'name' => 'KAM', 'user_id' => $u_admin],
            ['record_type' => 'purchase_order_low', 'level' => 2, 'name' => 'Finance', 'user_id' => $u_melvien],
            ['record_type' => 'purchase_order_low', 'level' => 3, 'name' => 'Procurement', 'user_id' => $u_febri],

            // PO Approval (> 1M)
            ['record_type' => 'purchase_order_high', 'level' => 1, 'name' => 'KAM', 'user_id' => $u_admin],
            ['record_type' => 'purchase_order_high', 'level' => 2, 'name' => 'Finance', 'user_id' => $u_melvien],
            ['record_type' => 'purchase_order_high', 'level' => 3, 'name' => 'CEO', 'user_id' => $u_barry],
        ];

        foreach ($configs as $cfg) {
            ErpApprovalConfig::create($cfg);
        }

        /* =======================================================
           4. MASTER DATA ERP
        ======================================================= */
        // UOM
        $uoms = ['PCS', 'BOX', 'KG', 'LITER', 'METER', 'UNIT', 'SET'];
        foreach ($uoms as $uom) {
            Uom::firstOrCreate(['uom_name' => $uom]);
        }

        // Product Family
        $families = ['Electronics', 'Furniture', 'Stationery', 'Network Equipment'];
        foreach ($families as $fam) {
            ProductFamily::firstOrCreate(['family_name' => $fam]);
        }

        // Product Type
        $types = ['Hardware', 'Software', 'Consumable', 'Service'];
        foreach ($types as $type) {
            ProductType::firstOrCreate(['type_name' => $type]);
        }

        // Brand
        $brands = ['Samsung', 'Apple', 'HP', 'Dell', 'Cisco', 'MikroTik', 'Logitech'];
        foreach ($brands as $brand) {
            Brand::firstOrCreate(['brand_name' => $brand]);
        }

        // Product Model
        $models = ['Model X', 'Pro Max', 'Latitude', 'Catalyst 2960', 'Standard'];
        foreach ($models as $mod) {
            ProductModel::firstOrCreate(['model_name' => $mod]);
        }

        // Currency
        $currencies = [
            ['code' => 'IDR', 'name' => 'Indonesian Rupiah', 'symbol' => 'Rp'],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        ];
        foreach ($currencies as $curr) {
            Currency::updateOrCreate(['code' => $curr['code']], $curr);
        }

        // ERP Suppliers
        $suppliers = [
            ['supplier_code' => 'SUP-001', 'name' => 'PT. Aneka Makmur', 'address' => 'Jakarta Pusat', 'phone' => '021-123456'],
            ['supplier_code' => 'SUP-002', 'name' => 'CV. Bina Usaha', 'address' => 'Bandung', 'phone' => '022-765432'],
            ['supplier_code' => 'SUP-003', 'name' => 'Anugrah Mandiri Telepower. PT', 'address' => 'Jakarta Utara', 'phone' => '021-998877'],
        ];
        foreach ($suppliers as $sup) {
            ErpSupplier::updateOrCreate(['supplier_code' => $sup['supplier_code']], $sup);
        }

        // ERP Destinations / Warehouses
        $warehouses = [
            ['warehouse_code' => 'WH-JKT', 'name' => 'Gudang Utama Jakarta', 'address' => 'Jakarta'],
            ['warehouse_code' => 'WH-SBY', 'name' => 'Gudang Surabaya', 'address' => 'Surabaya'],
            ['warehouse_code' => 'WH-MDN', 'name' => 'Gudang Medan', 'address' => 'Medan'],
        ];
        foreach ($warehouses as $wh) {
            ErpWarehouse::updateOrCreate(['warehouse_code' => $wh['warehouse_code']], $wh);
        }

        // Payment Terms (TOP)
        $paymentTerms = [
            ['name' => 'Cash On Delivery'],
            ['name' => 'Net 15 Days'],
            ['name' => 'Net 30 Days'],
            ['name' => 'Net 45 Days'],
        ];
        foreach ($paymentTerms as $pt) {
            ErpPaymentTerm::updateOrCreate(['name' => $pt['name']], $pt);
        }
    }
}
