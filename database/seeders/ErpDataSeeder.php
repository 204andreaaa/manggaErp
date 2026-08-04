<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Erp\Uom;
use App\Models\Erp\Currency;
use App\Models\Erp\Brand;
use App\Models\Erp\ProductFamily;
use App\Models\Erp\ProductType;
use App\Models\Erp\ProductModel;
use App\Models\Erp\ErpProduct;

class ErpDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Setup default UOMs
        $uomPcs = Uom::firstOrCreate(['uom_name' => 'Pcs'], ['description' => 'Pieces']);
        $uomBox = Uom::firstOrCreate(['uom_name' => 'Box'], ['description' => 'Box']);
        $uomRoll = Uom::firstOrCreate(['uom_name' => 'Roll'], ['description' => 'Roll']);
        
        // Setup default Currencies
        $currencyIdr = Currency::firstOrCreate(['code' => 'IDR', 'name' => 'Indonesian Rupiah']);
        $currencyUsd = Currency::firstOrCreate(['code' => 'USD', 'name' => 'US Dollar']);
        
        // Setup default Brands
        $brandSony = Brand::firstOrCreate(['brand_name' => 'Sony'], ['description' => 'Sony Electronics']);
        $brandSamsung = Brand::firstOrCreate(['brand_name' => 'Samsung'], ['description' => 'Samsung Electronics']);
        $brandCisco = Brand::firstOrCreate(['brand_name' => 'Cisco'], ['description' => 'Cisco Systems']);
        
        // Setup default Product Families
        $familyElectronics = ProductFamily::firstOrCreate(['family_name' => 'Electronics'], ['description' => 'Electronics']);
        $familyNetworking = ProductFamily::firstOrCreate(['family_name' => 'Networking'], ['description' => 'Networking']);
        
        // Setup default Product Types
        $typeAudio = ProductType::firstOrCreate(['type_name' => 'Audio Equipment'], ['description' => 'Audio Equipment']);
        $typeSwitch = ProductType::firstOrCreate(['type_name' => 'Network Switch'], ['description' => 'Network Switch']);
        
        // Setup default Product Models
        $modelA = ProductModel::firstOrCreate(['model_name' => 'Model A-100'], ['description' => 'Model A-100']);
        $modelB = ProductModel::firstOrCreate(['model_name' => 'Model B-200'], ['description' => 'Model B-200']);
        
        // Seed Products
        $products = [
            [
                'product_code' => 'PRD-001',
                'part_number' => 'PN-SNY-001',
                'name' => 'Sony Headphone Extra Bass',
                'description' => 'High quality extra bass headphone from Sony.',
                'uom_id' => $uomPcs->id,
                'buying_price' => 1500000,
                'product_family_id' => $familyElectronics->id,
                'product_type_id' => $typeAudio->id,
                'brand_id' => $brandSony->id,
                'product_model_id' => $modelA->id,
                'currency_id' => $currencyIdr->id,
                'is_active' => true,
            ],
            [
                'product_code' => 'PRD-002',
                'part_number' => 'PN-CIS-022',
                'name' => 'Cisco Catalyst 2960-X',
                'description' => '48-port Gigabit Ethernet network switch.',
                'uom_id' => $uomPcs->id,
                'buying_price' => 12500000,
                'product_family_id' => $familyNetworking->id,
                'product_type_id' => $typeSwitch->id,
                'brand_id' => $brandCisco->id,
                'product_model_id' => $modelB->id,
                'currency_id' => $currencyIdr->id,
                'is_active' => true,
            ],
            [
                'product_code' => 'PRD-003',
                'part_number' => 'PN-SAM-033',
                'name' => 'Samsung QLED TV 55 Inch',
                'description' => '4K QLED Smart TV from Samsung.',
                'uom_id' => $uomPcs->id,
                'buying_price' => 8500000,
                'product_family_id' => $familyElectronics->id,
                'product_type_id' => $typeAudio->id,
                'brand_id' => $brandSamsung->id,
                'product_model_id' => $modelA->id,
                'currency_id' => $currencyIdr->id,
                'is_active' => true,
            ]
        ];
        
        foreach ($products as $productData) {
            ErpProduct::updateOrCreate(
                ['product_code' => $productData['product_code']],
                $productData
            );
        }
        
        $this->command->info('ERP Master Data and Products seeded successfully!');
    }
}
