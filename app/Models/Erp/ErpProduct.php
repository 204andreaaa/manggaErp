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
}
