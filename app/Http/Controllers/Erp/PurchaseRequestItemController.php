<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Erp\PurchaseRequestItem;
use App\Models\Erp\ErpProduct;
use Illuminate\Http\Request;

class PurchaseRequestItemController extends Controller
{
    public function show(PurchaseRequestItem $purchaseRequestItem)
    {
        $purchaseRequestItem->load(['purchaseRequest.requestForm', 'requestFormItem', 'purchaseOrderItems.purchaseOrder']);
        
        // Find matching details from ErpProduct if possible
        $brand = '-';
        $model = '-';
        $uom = '-';
        if ($purchaseRequestItem->requestFormItem) {
            $product = ErpProduct::where('product_code', $purchaseRequestItem->requestFormItem->product_id_text)
                ->orWhere('name', $purchaseRequestItem->requestFormItem->product_name)
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
        }

        return view('erp.purchase_request_items.show', compact('purchaseRequestItem', 'uom', 'brand', 'model'));
    }
}
