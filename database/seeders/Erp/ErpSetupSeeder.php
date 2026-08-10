<?php

namespace Database\Seeders\Erp;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Role;
use App\Models\Project;
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
           0. DEFAULT PROJECT
        ======================================================= */
        $project = Project::updateOrCreate(
            ['db_name' => env('TENANT_DB_DATABASE', 'mandau_db')],
            [
                'name'        => 'Mangga ERP (Mandau)',
                'db_host'     => env('TENANT_DB_HOST', env('DB_HOST', '127.0.0.1')),
                'db_port'     => env('TENANT_DB_PORT', env('DB_PORT', '3306')),
                'db_username' => env('TENANT_DB_USERNAME', env('DB_USERNAME', 'root')),
                'db_password' => env('TENANT_DB_PASSWORD', env('DB_PASSWORD', '')),
                'is_active'   => true,
            ]
        );

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

            $u->projects()->syncWithoutDetaching([$project->id]);
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

        $ptNet30 = ErpPaymentTerm::where('name', 'Net 30 Days')->first()?->id;
        $ptNet15 = ErpPaymentTerm::where('name', 'Net 15 Days')->first()?->id;
        $ptCOD   = ErpPaymentTerm::where('name', 'Cash On Delivery')->first()?->id;

        // ERP Suppliers with full details & contacts
        $suppliersData = [
            [
                'supplier_code'    => 'SUP-001',
                'name'             => 'PT. Aneka Makmur',
                'address'          => 'Jl. Jendral Sudirman No. 45, Lantai 8, Jakarta Pusat 10210',
                'phone'            => '021-57901234',
                'bank_name'        => 'BCA',
                'bank_account'     => '1234567890 a/n PT Aneka Makmur',
                'payment_terms_id' => $ptNet30,
                'contacts'         => [
                    ['contact_name' => 'Budi Santoso', 'title' => 'Account Manager', 'email' => 'budi@anekamakmur.co.id', 'phone' => '081298765432'],
                    ['contact_name' => 'Siti Rahmawati', 'title' => 'Finance & Billing', 'email' => 'finance@anekamakmur.co.id', 'phone' => '081311223344'],
                ]
            ],
            [
                'supplier_code'    => 'SUP-002',
                'name'             => 'CV. Bina Usaha',
                'address'          => 'Jl. Asia Afrika No. 112, Bandung, Jawa Barat 40112',
                'phone'            => '022-4235678',
                'bank_name'        => 'Bank Mandiri',
                'bank_account'     => '1310009876543 a/n CV Bina Usaha',
                'payment_terms_id' => $ptNet15,
                'contacts'         => [
                    ['contact_name' => 'Hendra Wijaya', 'title' => 'Sales Executive', 'email' => 'hendra@binausaha.com', 'phone' => '081809090909'],
                    ['contact_name' => 'Dewi Anggraini', 'title' => 'Customer Service', 'email' => 'cs@binausaha.com', 'phone' => '081912345678'],
                ]
            ],
            [
                'supplier_code'    => 'SUP-003',
                'name'             => 'Anugrah Mandiri Telepower. PT',
                'address'          => 'Kawasan Industri Pulogadung Block B No. 9, Jakarta Utara 13920',
                'phone'            => '021-4609988',
                'bank_name'        => 'BNI',
                'bank_account'     => '0987654321 a/n PT Anugrah Mandiri Telepower',
                'payment_terms_id' => $ptNet30,
                'contacts'         => [
                    ['contact_name' => 'Irwan Kurniawan', 'title' => 'Technical Sales Manager', 'email' => 'irwan@telepower.co.id', 'phone' => '081122334455'],
                    ['contact_name' => 'Rina Marlina', 'title' => 'Admin Logistics', 'email' => 'logistics@telepower.co.id', 'phone' => '081233445566'],
                ]
            ],
            [
                'supplier_code'    => 'SUP-004',
                'name'             => 'PT. Cisco Systems Indonesia',
                'address'          => 'World Trade Centre 3, Lt. 18, Jl. Jend. Sudirman Kav 29-31, Jakarta Selatan 12920',
                'phone'            => '021-29955000',
                'bank_name'        => 'Bank Permata',
                'bank_account'     => '4101928374 a/n PT Cisco Systems Indonesia',
                'payment_terms_id' => $ptNet30,
                'contacts'         => [
                    ['contact_name' => 'Alex Chandra', 'title' => 'Enterprise Partner Manager', 'email' => 'achandra@cisco.com', 'phone' => '081700998877'],
                ]
            ],
            [
                'supplier_code'    => 'SUP-005',
                'name'             => 'PT. Data Komputindo Utama',
                'address'          => 'Ruko Mangga Dua Mall No. 34, Jakarta Pusat 10730',
                'phone'            => '021-6123456',
                'bank_name'        => 'BCA',
                'bank_account'     => '8800112233 a/n PT Data Komputindo Utama',
                'payment_terms_id' => $ptCOD,
                'contacts'         => [
                    ['contact_name' => 'Kevin Pratama', 'title' => 'Senior Procurement Specialist', 'email' => 'kevin@datakomputindo.co.id', 'phone' => '081555667788'],
                ]
            ]
        ];

        foreach ($suppliersData as $supData) {
            $contacts = $supData['contacts'] ?? [];
            unset($supData['contacts']);

            $supplier = ErpSupplier::updateOrCreate(['supplier_code' => $supData['supplier_code']], $supData);

            $supplier->contacts()->delete();
            foreach ($contacts as $contact) {
                $supplier->contacts()->create($contact);
            }
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
    }
}
