<?php

namespace App\Models;

use App\Enums\EnumProduct;
use App\Traits\FullTextSearch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Products extends CoreModel
{
    use HasFactory, SoftDeletes, FullTextSearch;

    protected $table = 'dtb_products';

    protected $searchable = [
        'dtb_products.name'
    ];

    protected $hidden = ['pivot'];

    protected $fillable = [
        'name',
        'store_id',
        'status',
        'description',
        'category_id',
        'price',
        'discount',
        'stock'
    ];

    public function productMedias()
    {
        return $this->hasMany(ProductMedia::class, 'product_id')->select('product_id', 'media_type', 'media_path');
    }

    public function productMediasImage()
    {
        return $this->hasOne(ProductMedia::class, 'product_id')->select('product_id', 'media_type', 'media_path')
            ->where('media_type', EnumProduct::MEDIA_TYPE_IMAGE);
    }

    // public function productClasses(): HasMany
    // {
    //     return $this->hasMany(ProductClass::class, 'product_id', 'id');
    // }

    public function productFavorites(): HasMany
    {
        return $this->hasMany(ProductFavorite::class, 'product_id', 'id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }

    // public function productTypeConfig(): HasMany
    // {
    //     return $this->hasMany(ProductTypeConfig::class, 'product_id', 'id');
    // }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }
}
