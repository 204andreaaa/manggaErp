<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Erp\RequestFormItem;
use App\Models\Erp\ErpProduct;
use Illuminate\Http\Request;

class RequestFormItemController extends Controller
{
    public function show(RequestFormItem $requestFormItem)
    {
        $requestFormItem->load(['requestForm', 'purchaseRequestItems.purchaseRequest']);
        
        $brand = '-';
        $model = '-';
        $uom = '-';
        $product = ErpProduct::where('product_code', $requestFormItem->product_id_text)
            ->orWhere('name', $requestFormItem->product_name)
            ->with(['uom', 'brand', 'productModel'])
            ->first();
        if ($product) {
            if ($product->uom) {
                $uom = $product->uom->uom_name;
            }
            if ($product->brand) {
                $brand = $product->brand->brand_name;
            }
            if ($product->productModel) {
                $model = $product->productModel->model_name;
            }
        }

        return view('erp.request_form_items.show', compact('requestFormItem', 'uom', 'brand', 'model'));
    }
}
