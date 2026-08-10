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
        $brandId    = Brand::first()?->id;
        $currencyId = Currency::first()?->id;
        $familyId   = ProductFamily::first()?->id;
        $typeId     = ProductType::first()?->id;
        $modelId    = ProductModel::first()?->id;
        $uomId      = Uom::first()?->id;

        $products = [
            [
                'product_code'      => 'PRD-001',
                'part_number'        => 'PN-1001',
                'name'               => 'Laptop Pro 15 (Core i7 / 16GB / 512GB)',
                'description'        => 'High performance workstation laptop',
                'image'              => 'uploads/products/product_1785466284_rG943t.jpg',
                'uom_id'             => $uomId,
                'buying_price'       => 15000000,
                'is_physical'        => true,
                'product_family_id'  => $familyId,
                'product_type_id'    => $typeId,
                'brand_id'           => $brandId,
                'product_model_id'   => $modelId,
                'currency_id'        => $currencyId,
                'is_active'          => true,
            ],
            [
                'product_code'      => 'PRD-002',
                'part_number'        => 'PN-1002',
                'name'               => 'Wireless Optical Mouse',
                'description'        => 'Ergonomic wireless mouse 2.4GHz',
                'image'              => 'uploads/products/product_1785467148_6V2r1B.jpg',
                'uom_id'             => $uomId,
                'buying_price'       => 250000,
                'is_physical'        => true,
                'product_family_id'  => $familyId,
                'product_type_id'    => $typeId,
                'brand_id'           => $brandId,
                'product_model_id'   => $modelId,
                'currency_id'        => $currencyId,
                'is_active'          => true,
            ],
            [
                'product_code'      => 'PRD-003',
                'part_number'        => 'PN-1003',
                'name'               => 'Mechanical Keyboard RGB',
                'description'        => 'Tactile switch mechanical keyboard',
                'image'              => 'uploads/products/product_1785467180_9JCZWU.jpg',
                'uom_id'             => $uomId,
                'buying_price'       => 1200000,
                'is_physical'        => true,
                'product_family_id'  => $familyId,
                'product_type_id'    => $typeId,
                'brand_id'           => $brandId,
                'product_model_id'   => $modelId,
                'currency_id'        => $currencyId,
                'is_active'          => true,
            ],
            [
                'product_code'      => 'PRD-004',
                'part_number'        => 'PN-1004',
                'name'               => '27-inch 4K IPS Monitor',
                'description'        => 'Ultra HD 4K color calibrated display',
                'image'              => 'uploads/products/product_1785467211_ORihUI.jpg',
                'uom_id'             => $uomId,
                'buying_price'       => 4500000,
                'is_physical'        => true,
                'product_family_id'  => $familyId,
                'product_type_id'    => $typeId,
                'brand_id'           => $brandId,
                'product_model_id'   => $modelId,
                'currency_id'        => $currencyId,
                'is_active'          => true,
            ],
            [
                'product_code'      => 'PRD-005',
                'part_number'        => 'PN-1005',
                'name'               => 'USB-C Docking Station 7-in-1',
                'description'        => 'Multi-port USB-C adapter with HDMI & Ethernet',
                'image'              => 'uploads/products/product_1785467114_M7mgc8.jpg',
                'uom_id'             => $uomId,
                'buying_price'       => 600000,
                'is_physical'        => true,
                'product_family_id'  => $familyId,
                'product_type_id'    => $typeId,
                'brand_id'           => $brandId,
                'product_model_id'   => $modelId,
                'currency_id'        => $currencyId,
                'is_active'          => true,
            ],
            [
                'product_code'      => 'PRD-006',
                'part_number'        => 'PN-1006',
                'name'               => 'Cisco Catalyst Switch 24-Port Gigabit',
                'description'        => 'Managed enterprise switch 24-port PoE+',
                'image'              => 'uploads/products/product_1785467057_0bCR5e.jpg',
                'uom_id'             => $uomId,
                'buying_price'       => 8500000,
                'is_physical'        => true,
                'product_family_id'  => $familyId,
                'product_type_id'    => $typeId,
                'brand_id'           => $brandId,
                'product_model_id'   => $modelId,
                'currency_id'        => $currencyId,
                'is_active'          => true,
            ],
            [
                'product_code'      => 'PRD-007',
                'part_number'        => 'PN-1007',
                'name'               => 'MikroTik RouterBoard RB3011UiAS-RM',
                'description'        => '10x Gigabit Ethernet router with LCD',
                'image'              => 'uploads/products/product_1785467057_0bCR5e.jpg',
                'uom_id'             => $uomId,
                'buying_price'       => 3200000,
                'is_physical'        => true,
                'product_family_id'  => $familyId,
                'product_type_id'    => $typeId,
                'brand_id'           => $brandId,
                'product_model_id'   => $modelId,
                'currency_id'        => $currencyId,
                'is_active'          => true,
            ],
            [
                'product_code'      => 'PRD-008',
                'part_number'        => 'PN-1008',
                'name'               => 'Kabel Fiber Optik Single Mode 100m',
                'description'        => 'High speed outdoor fiber optic cable',
                'image'              => 'uploads/products/product_1785467114_M7mgc8.jpg',
                'uom_id'             => $uomId,
                'buying_price'       => 750000,
                'is_physical'        => true,
                'product_family_id'  => $familyId,
                'product_type_id'    => $typeId,
                'brand_id'           => $brandId,
                'product_model_id'   => $modelId,
                'currency_id'        => $currencyId,
                'is_active'          => true,
            ],
            [
                'product_code'      => 'PRD-009',
                'part_number'        => 'PN-1009',
                'name'               => 'Server Dell PowerEdge R640 1U',
                'description'        => 'Rack server Xeon Silver / 64GB RAM / 2x1TB SSD',
                'image'              => 'uploads/products/product_1785467057_0bCR5e.jpg',
                'uom_id'             => $uomId,
                'buying_price'       => 42000000,
                'is_physical'        => true,
                'product_family_id'  => $familyId,
                'product_type_id'    => $typeId,
                'brand_id'           => $brandId,
                'product_model_id'   => $modelId,
                'currency_id'        => $currencyId,
                'is_active'          => true,
            ],
            [
                'product_code'      => 'PRD-010',
                'part_number'        => 'PN-1010',
                'name'               => 'UPS APC Smart-UPS 1500VA LCD 230V',
                'description'        => 'Uninterruptible power supply 1500VA',
                'image'              => 'uploads/products/product_1785467211_ORihUI.jpg',
                'uom_id'             => $uomId,
                'buying_price'       => 6800000,
                'is_physical'        => true,
                'product_family_id'  => $familyId,
                'product_type_id'    => $typeId,
                'brand_id'           => $brandId,
                'product_model_id'   => $modelId,
                'currency_id'        => $currencyId,
                'is_active'          => true,
            ],
        ];

        foreach ($products as $product) {
            ErpProduct::updateOrCreate(
                ['product_code' => $product['product_code']],
                $product
            );
        }
    }
}
