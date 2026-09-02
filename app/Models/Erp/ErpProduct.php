<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Package;

class ErpProduct extends Model
{
    protected $connection = 'tenant';
    use SoftDeletes;

    protected $fillable = [
        'product_code',
        'part_number',
        'name',
        'image',
        'description',
        'uom_id',
        'buying_price',
        'is_physical',
        'product_family_id',
        'product_type_id',
        'brand_id',
        'product_model_id',
        'currency_id',
        'is_active'
    ];

    protected $casts = [
        'buying_price' => 'integer',
        'is_physical' => 'boolean',
        'is_active' => 'boolean'
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if (!empty($this->image) && file_exists(public_path($this->image))) {
            return asset($this->image);
        }
        return null;
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class);
    }

    public function productFamily()
    {
        return $this->belongsTo(ProductFamily::class);
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function productModel()
    {
        return $this->belongsTo(ProductModel::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function stocks()
    {
        return $this->hasMany(ErpStock::class, 'erp_product_id');
    }

    public static function syncBuyingPriceFromLatestApprovedPo($productId)
    {
        $product = self::find($productId);
        if (!$product) return;

        $latestPoItem = ErpPurchaseOrderItem::whereHas('requestFormItem', function($q) use ($product) {
                $q->where('product_id_text', $product->product_code)
                  ->orWhere('product_name', $product->name);
            })
            ->whereHas('purchaseOrder', function($q) {
                $q->where('status', 'Approved');
            })
            ->join('erp_purchase_orders', 'erp_purchase_order_items.purchase_order_id', '=', 'erp_purchase_orders.id')
            ->orderByRaw('COALESCE(erp_purchase_orders.approved_date, erp_purchase_orders.updated_at) DESC')
            ->orderBy('erp_purchase_orders.id', 'desc')
            ->select('erp_purchase_order_items.*')
            ->first();

        if ($latestPoItem && $latestPoItem->unit_cost > 0) {
            $product->update([
                'buying_price' => (int) $latestPoItem->unit_cost
            ]);
        }
    }

    public static function syncProductsFromPo(ErpPurchaseOrder $po)
    {
        $po->loadMissing('items.requestFormItem.erpProduct');
        foreach ($po->items as $item) {
            $product = $item->requestFormItem?->erpProduct 
                ?: self::where('product_code', $item->requestFormItem?->product_id_text)
                    ->orWhere('name', $item->requestFormItem?->product_name)
                    ->first();

            if ($product) {
                self::syncBuyingPriceFromLatestApprovedPo($product->id);
            }
        }
    }
}

