<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Erp\ErpProduct;
use App\Models\Erp\Brand;
use App\Models\Erp\Currency;
use App\Models\Erp\ProductFamily;
use App\Models\Erp\ProductType;
use App\Models\Erp\ProductModel;
use App\Models\Erp\Uom;

class ErpProductSeeder extends Seeder
{
    public function run()
    {
        $brandId = Brand::first()->id ?? null;
        $currencyId = Currency::first()->id ?? null;
        $familyId = ProductFamily::first()->id ?? null;
        $typeId = ProductType::first()->id ?? null;
        $modelId = ProductModel::first()->id ?? null;
        $uomId = Uom::first()->id ?? null;

        $products = [
            [
                'product_code' => 'PRD-001',
                'part_number' => 'PN-1001',
                'name' => 'Laptop Pro 15',
                'description' => 'High performance laptop',
                'uom_id' => $uomId,
                'buying_price' => 15000000,
                'product_family_id' => $familyId,
                'product_type_id' => $typeId,
                'brand_id' => $brandId,
                'product_model_id' => $modelId,
                'currency_id' => $currencyId,
                'is_active' => true,
            ],
            [
                'product_code' => 'PRD-002',
                'part_number' => 'PN-1002',
                'name' => 'Wireless Mouse',
                'description' => 'Ergonomic wireless mouse',
                'uom_id' => $uomId,
                'buying_price' => 250000,
                'product_family_id' => $familyId,
                'product_type_id' => $typeId,
                'brand_id' => $brandId,
                'product_model_id' => $modelId,
                'currency_id' => $currencyId,
                'is_active' => true,
            ],
            [
                'product_code' => 'PRD-003',
                'part_number' => 'PN-1003',
                'name' => 'Mechanical Keyboard',
                'description' => 'RGB mechanical keyboard',
                'uom_id' => $uomId,
                'buying_price' => 1200000,
                'product_family_id' => $familyId,
                'product_type_id' => $typeId,
                'brand_id' => $brandId,
                'product_model_id' => $modelId,
                'currency_id' => $currencyId,
                'is_active' => true,
            ],
            [
                'product_code' => 'PRD-004',
                'part_number' => 'PN-1004',
                'name' => '27-inch Monitor',
                'description' => '4K IPS monitor',
                'uom_id' => $uomId,
                'buying_price' => 4500000,
                'product_family_id' => $familyId,
                'product_type_id' => $typeId,
                'brand_id' => $brandId,
                'product_model_id' => $modelId,
                'currency_id' => $currencyId,
                'is_active' => true,
            ],
            [
                'product_code' => 'PRD-005',
                'part_number' => 'PN-1005',
                'name' => 'USB-C Hub',
                'description' => '7-in-1 USB-C Hub',
                'uom_id' => $uomId,
                'buying_price' => 600000,
                'product_family_id' => $familyId,
                'product_type_id' => $typeId,
                'brand_id' => $brandId,
                'product_model_id' => $modelId,
                'currency_id' => $currencyId,
                'is_active' => true,
            ]
        ];

        foreach ($products as $product) {
            ErpProduct::firstOrCreate(
                ['product_code' => $product['product_code']],
                $product
            );
        }
    }
}
