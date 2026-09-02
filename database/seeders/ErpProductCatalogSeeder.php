<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Erp\ErpProduct;
use App\Models\Erp\Uom;
use App\Models\Erp\ProductFamily;
use App\Models\Erp\ProductType;
use App\Models\Erp\Brand;
use App\Models\Erp\ProductModel;
use App\Models\Erp\Currency;
use App\Models\Erp\ErpSupplier;
use App\Models\Erp\ErpPaymentTerm;

class ErpProductCatalogSeeder extends Seeder
{
    public function run()
    {
        DB::connection('tenant')->statement('SET FOREIGN_KEY_CHECKS=0;');
        ErpProduct::truncate();
        DB::connection('tenant')->statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Currency
        $idr = Currency::firstOrCreate(
            ['code' => 'IDR'],
            ['name' => 'Indonesian Rupiah', 'symbol' => 'Rp', 'is_default' => true]
        );

        // 2. UOMs
        $uoms = [
            'ROLL'   => Uom::firstOrCreate(['uom_name' => 'ROLL'], ['description' => 'Gulungan / Roll']),
            'DRUM'   => Uom::firstOrCreate(['uom_name' => 'DRUM'], ['description' => 'Drum Kayu Besar']),
            'PCS'    => Uom::firstOrCreate(['uom_name' => 'PCS'], ['description' => 'Pieces / Buah']),
            'UNIT'   => Uom::firstOrCreate(['uom_name' => 'UNIT'], ['description' => 'Unit Perangkat']),
            'SET'    => Uom::firstOrCreate(['uom_name' => 'SET'], ['description' => 'Satu Set Lengkap']),
            'BATANG' => Uom::firstOrCreate(['uom_name' => 'BATANG'], ['description' => 'Batang 5 Meter']),
            'METER'  => Uom::firstOrCreate(['uom_name' => 'METER'], ['description' => 'Satuan Meter']),
        ];

        // 3. Product Families
        $famFo       = ProductFamily::firstOrCreate(['family_name' => 'Fiber Optic & Kabel'], ['description' => 'Kabel FO dan aksesoris optik']);
        $famNet      = ProductFamily::firstOrCreate(['family_name' => 'Networking & Active Devices'], ['description' => 'Perangkat aktif jaringan']);
        $famTower    = ProductFamily::firstOrCreate(['family_name' => 'Konstruksi Tower & Besi'], ['description' => 'Material dan aksesoris tower triangle']);
        $famGround   = ProductFamily::firstOrCreate(['family_name' => 'Material Grounding & Proteksi'], ['description' => 'Proteksi petir dan kabel arde']);
        $famWireless = ProductFamily::firstOrCreate(['family_name' => 'Wireless & Radio Backhaul'], ['description' => 'Perangkat transmisi wireless']);

        // 4. Product Types
        $typeMat = ProductType::firstOrCreate(['type_name' => 'Material Pasif'], ['description' => 'Material instalasi fisik']);
        $typeDev = ProductType::firstOrCreate(['type_name' => 'Perangkat Keras (Hardware)'], ['description' => 'Perangkat aktif berdaya listrik']);
        $typeAcc = ProductType::firstOrCreate(['type_name' => 'Aksesoris & Konektor'], ['description' => 'Komponen pendukung instalasi']);

        // 5. Brands
        $brands = [
            'Netviell'     => Brand::firstOrCreate(['brand_name' => 'Netviell']),
            'CCSI'         => Brand::firstOrCreate(['brand_name' => 'CCSI']),
            'Mikrotik'     => Brand::firstOrCreate(['brand_name' => 'Mikrotik']),
            'Fiberhome'    => Brand::firstOrCreate(['brand_name' => 'Fiberhome']),
            'Ruijie'       => Brand::firstOrCreate(['brand_name' => 'Ruijie Reyee']),
            'Cisco'        => Brand::firstOrCreate(['brand_name' => 'Cisco Systems']),
            'Mandau Steel' => Brand::firstOrCreate(['brand_name' => 'Mandau Steel']),
            'Kiswire'      => Brand::firstOrCreate(['brand_name' => 'Kiswire']),
            'Crosby'       => Brand::firstOrCreate(['brand_name' => 'Crosby']),
            'Indolite'     => Brand::firstOrCreate(['brand_name' => 'Indolite']),
            'Kurn'         => Brand::firstOrCreate(['brand_name' => 'Kurn Indonesia']),
            'Supreme'      => Brand::firstOrCreate(['brand_name' => 'Supreme Cable']),
            'Ubiquiti'     => Brand::firstOrCreate(['brand_name' => 'Ubiquiti (UBNT)']),
        ];

        // 6. Suppliers & Payment Terms
        $termNet30 = ErpPaymentTerm::firstOrCreate(['name' => 'NET 30'], ['is_active' => true, 'term_schedule' => [['name' => 'Pelunasan 100%', 'percentage' => 100, 'type' => 'Invoice']]]);
        $termDp50  = ErpPaymentTerm::firstOrCreate(['name' => 'DP 50% - Pelunasan 50%'], ['is_active' => true, 'term_schedule' => [['name' => 'DP 50%', 'percentage' => 50, 'type' => 'DP'], ['name' => 'Pelunasan 50%', 'percentage' => 50, 'type' => 'Pelunasan']]]);

        ErpSupplier::firstOrCreate(
            ['supplier_code' => 'SUP-TEL-001'],
            [
                'name' => 'PT. Fiberindo Solusi Nusantara',
                'category' => 'Fiber Optic & Network',
                'payment_terms_id' => $termNet30->id,
                'phone' => '021-5558901',
                'address' => 'Kawasan Industri Pulogadung, Jakarta Timur'
            ]
        );

        ErpSupplier::firstOrCreate(
            ['supplier_code' => 'SUP-TOW-002'],
            [
                'name' => 'PT. Mandau Mega Konstruksi',
                'category' => 'Tower & Civil Metal',
                'payment_terms_id' => $termDp50->id,
                'phone' => '0761-889021',
                'address' => 'Jl. Soekarno Hatta No. 45, Pekanbaru'
            ]
        );

        ErpSupplier::firstOrCreate(
            ['supplier_code' => 'SUP-NET-003'],
            [
                'name' => 'PT. Sinergi Komunikasi Utama',
                'category' => 'Active Hardware & Wireless',
                'payment_terms_id' => $termNet30->id,
                'phone' => '021-8899201',
                'address' => 'Mangga Dua Square Blok F No. 12, Jakarta Utara'
            ]
        );

        // 7. Catalog Products Data List
        $products = [
            // ==================== KELOMPOK 1: JSI (MS & MAINTENANCE JARINGAN) ====================
            [
                'product_code'      => 'PRD-FO-001',
                'part_number'       => 'NV-DC-1C3S-1KM',
                'name'              => 'Kabel Fiber Optic Drop Core 1 Core 3 Seling (1000m)',
                'description'       => 'Kabel FO Drop Cable 1 Core G657A1 dengan 3 kawat seling baja penguat (1 messenger + 2 member). Panjang 1000 meter per roll.',
                'uom_id'            => $uoms['ROLL']->id,
                'buying_price'      => 650000,
                'product_family_id' => $famFo->id,
                'product_type_id'   => $typeMat->id,
                'brand_id'          => $brands['Netviell']->id,
            ],
            [
                'product_code'      => 'PRD-FO-002',
                'part_number'       => 'CCSI-ADSS-24C-2KM',
                'name'              => 'Kabel Fiber Optic ADSS 24 Core Single Mode (2000m)',
                'description'       => 'Kabel udara All-Dielectric Self-Supporting (ADSS) 24 Core Single Mode untuk bentangan tiang antar kota. Panjang 2000m drum kayu.',
                'uom_id'            => $uoms['DRUM']->id,
                'buying_price'      => 14500000,
                'product_family_id' => $famFo->id,
                'product_type_id'   => $typeMat->id,
                'brand_id'          => $brands['CCSI']->id,
            ],
            [
                'product_code'      => 'PRD-FO-003',
                'part_number'       => 'PC-SC-LC-SM-3M',
                'name'              => 'Patch Cord SC-UPC to LC-UPC Simplex 3 Meter',
                'description'       => 'Kabel jumper optik Single Mode 9/125um simplex panjang 3 meter dengan jaket LSZH low loss.',
                'uom_id'            => $uoms['PCS']->id,
                'buying_price'      => 25000,
                'product_family_id' => $famFo->id,
                'product_type_id'   => $typeAcc->id,
                'brand_id'          => $brands['Netviell']->id,
            ],
            [
                'product_code'      => 'PRD-NET-001',
                'part_number'       => 'MT-SFP-10G-LR-10KM',
                'name'              => 'SFP+ Transceiver Module 10G Single Mode 10KM (1310nm)',
                'description'       => 'Modul optical SFP+ 10Gbps LC duplex wavelength 1310nm jarak jangkau hingga 10 kilometer.',
                'uom_id'            => $uoms['UNIT']->id,
                'buying_price'      => 750000,
                'product_family_id' => $famNet->id,
                'product_type_id'   => $typeDev->id,
                'brand_id'          => $brands['Mikrotik']->id,
            ],
            [
                'product_code'      => 'PRD-FO-004',
                'part_number'       => 'FH-OTB-24C-1U',
                'name'              => 'OTB (Optical Termination Box) 24 Core Rackmount 1U',
                'description'       => 'Kotak terminasi optik rackmount 19 inch 1U lengkap dengan 24 adapter SC simplex, pigtail & splice tray.',
                'uom_id'            => $uoms['UNIT']->id,
                'buying_price'      => 450000,
                'product_family_id' => $famFo->id,
                'product_type_id'   => $typeMat->id,
                'brand_id'          => $brands['Fiberhome']->id,
            ],
            [
                'product_code'      => 'PRD-NET-002',
                'part_number'       => 'RG-NBS3100-24GT4SFP-P',
                'name'              => 'Switch Managed 24 Port Gigabit PoE+ 370W Ruijie',
                'description'       => 'Layer 2 Managed Cloud Switch 24 Port 10/100/1000 Base-T PoE+ (Power Budget 370 Watt) + 4 Port SFP Uplink.',
                'uom_id'            => $uoms['UNIT']->id,
                'buying_price'      => 4200000,
                'product_family_id' => $famNet->id,
                'product_type_id'   => $typeDev->id,
                'brand_id'          => $brands['Ruijie']->id,
            ],
            [
                'product_code'      => 'PRD-NET-003',
                'part_number'       => 'CCR2004-16G-2S-PLUS',
                'name'              => 'Mikrotik Cloud Core Router CCR2004-16G-2S+',
                'description'       => 'Core Router 4-Core CPU Annapurna Labs AL32400 1.7GHz, 4GB RAM, 16 Port Gigabit LAN, 2 Port 10G SFP+ Dual PSU.',
                'uom_id'            => $uoms['UNIT']->id,
                'buying_price'      => 7800000,
                'product_family_id' => $famNet->id,
                'product_type_id'   => $typeDev->id,
                'brand_id'          => $brands['Mikrotik']->id,
            ],

            // ==================== KELOMPOK 2: APJATEL (TOWER & RELOKASI) ====================
            [
                'product_code'      => 'PRD-TOW-001',
                'part_number'       => 'MND-TWR-TRI30-5M',
                'name'              => 'Besi Tower Triangle 30cm Galvanis Hot Dip (5 Meter)',
                'description'       => 'Batang tower triangle bentang 30cm pipa medium SNI finishing Hot Dip Galvanis anti karat. Termasuk baut mur grade 8.8.',
                'uom_id'            => $uoms['BATANG']->id,
                'buying_price'      => 1750000,
                'product_family_id' => $famTower->id,
                'product_type_id'   => $typeMat->id,
                'brand_id'          => $brands['Mandau Steel']->id,
            ],
            [
                'product_code'      => 'PRD-TOW-002',
                'part_number'       => 'KSW-GW-6MM-100M',
                'name'              => 'Kawat Seling Baja Galvanis 6mm (Guy Wire / Roll 100m)',
                'description'       => 'Kawat seling baja tali pancang tower diameter 6mm konstruksi 7x7 baja galvanis berkekuatan tarik tinggi.',
                'uom_id'            => $uoms['ROLL']->id,
                'buying_price'      => 850000,
                'product_family_id' => $famTower->id,
                'product_type_id'   => $typeMat->id,
                'brand_id'          => $brands['Kiswire']->id,
            ],
            [
                'product_code'      => 'PRD-TOW-003',
                'part_number'       => 'CRB-TB-M16-EE',
                'name'              => 'Turnbuckle / Spanscrew Jarum Keras M16 (Trekstang)',
                'description'       => 'Jarum keras penegang kawat seling tower ukuran M16 Eye to Eye galvanis heavy duty.',
                'uom_id'            => $uoms['PCS']->id,
                'buying_price'      => 125000,
                'product_family_id' => $famTower->id,
                'product_type_id'   => $typeAcc->id,
                'brand_id'          => $brands['Crosby']->id,
            ],
            [
                'product_code'      => 'PRD-TOW-004',
                'part_number'       => 'IND-OBL-LED-SOLAR',
                'name'              => 'Lampu Tower Obstruction Light (OBL LED Solar Panel)',
                'description'       => 'Lampu peringatan penerbangan tower warna merah kedip otomatis sensor cahaya bertenaga solar panel & internal battery.',
                'uom_id'            => $uoms['SET']->id,
                'buying_price'      => 2300000,
                'product_family_id' => $famTower->id,
                'product_type_id'   => $typeDev->id,
                'brand_id'          => $brands['Indolite']->id,
            ],
            [
                'product_code'      => 'PRD-GRD-001',
                'part_number'       => 'KRN-LIGHTNING-34-SET',
                'name'              => 'Penangkal Petir Splitzen Tembaga 3/4" + Grounding Rod (3m)',
                'description'       => 'Ujung tombak tembaga murni 3/4 inch + as grounding rod tembaga 5/8 inch panjang 3 meter + klem grounding.',
                'uom_id'            => $uoms['SET']->id,
                'buying_price'      => 1850000,
                'product_family_id' => $famGround->id,
                'product_type_id'   => $typeMat->id,
                'brand_id'          => $brands['Kurn']->id,
            ],
            [
                'product_code'      => 'PRD-GRD-002',
                'part_number'       => 'SUP-BC-50MM-MTR',
                'name'              => 'Kabel Tembaga BC (Bare Copper) 50mm Grounding Tower',
                'description'       => 'Kabel tembaga telanjang murni tanpa isolasi ukuran 50mm2 standar PLN/Telkom untuk penyalur petir tower ke tanah.',
                'uom_id'            => $uoms['METER']->id,
                'buying_price'      => 95000,
                'product_family_id' => $famGround->id,
                'product_type_id'   => $typeMat->id,
                'brand_id'          => $brands['Supreme']->id,
            ],
            [
                'product_code'      => 'PRD-TOW-005',
                'part_number'       => 'MND-BRK-ANT-UBOLT',
                'name'              => 'Bracket Antena & Siku Klem Pipa Heavy Duty Galvanis',
                'description'       => 'Bracket mounting dudukan antena radio wireless ke pipa tower triangle lengkap dengan u-bolt & mur galvanis.',
                'uom_id'            => $uoms['SET']->id,
                'buying_price'      => 350000,
                'product_family_id' => $famTower->id,
                'product_type_id'   => $typeAcc->id,
                'brand_id'          => $brands['Mandau Steel']->id,
            ],
            [
                'product_code'      => 'PRD-WRL-001',
                'part_number'       => 'UBNT-RP-5AC-PRISM-DISH30',
                'name'              => 'Radio Wireless Point-to-Point 5GHz Rocket Prism + Dish 30dBi',
                'description'       => 'Perangkat radio outdoor backhaul 5GHz airMAX ac 500+ Mbps dengan antena parabola RocketDish 30dBi untuk link jarak jauh.',
                'uom_id'            => $uoms['SET']->id,
                'buying_price'      => 6900000,
                'product_family_id' => $famWireless->id,
                'product_type_id'   => $typeDev->id,
                'brand_id'          => $brands['Ubiquiti']->id,
            ],
        ];

        foreach ($products as $p) {
            ErpProduct::create(array_merge($p, [
                'currency_id' => $idr->id,
                'is_physical' => true,
                'is_active'   => true,
            ]));
        }
    }
}
