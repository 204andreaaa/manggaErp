<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Erp\Brand;
use App\Models\Erp\Currency;
use App\Models\Erp\ProductFamily;
use App\Models\Erp\ProductType;
use App\Models\Erp\ProductModel;
use App\Models\Erp\Uom;

class ErpMasterDataSeeder extends Seeder
{
    public function run()
    {
        $brands = ['Sony', 'Samsung', 'LG', 'Panasonic'];
        foreach ($brands as $brand) {
            Brand::firstOrCreate(['brand_name' => $brand]);
        }

        $currencies = ['IDR' => 'Indonesian Rupiah', 'USD' => 'US Dollar', 'SGD' => 'Singapore Dollar'];
        foreach ($currencies as $code => $name) {
            Currency::firstOrCreate(['code' => $code], ['name' => $name, 'symbol' => $code]);
        }

        $families = ['Electronics', 'Home Appliances', 'Office Supplies'];
        foreach ($families as $family) {
            ProductFamily::firstOrCreate(['family_name' => $family]);
        }

        $types = ['Finished Goods', 'Raw Materials', 'Spare Parts'];
        foreach ($types as $type) {
            ProductType::firstOrCreate(['type_name' => $type]);
        }

        $models = ['Model A', 'Model B', 'Model C'];
        foreach ($models as $model) {
            ProductModel::firstOrCreate(['model_name' => $model]);
        }

        $uoms = ['Pcs', 'Box', 'Kg', 'Ltr'];
        foreach ($uoms as $uom) {
            Uom::firstOrCreate(['uom_name' => $uom]);
        }
    }
}
