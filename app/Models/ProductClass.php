<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductClass extends CoreModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'dtb_product_classes';

    protected $hidden = ['pivot'];

    protected $fillable = [
        'product_id',
        'status',
        'has_type_config',
        'name',
        'price',
        'sale_from',
        'sale_to',
        'stock',
        'sale_limit',
        'discount'
    ];

    public function product()
    {
        return $this->belongsTo(Products::class);
    }

    public function subOrders()
    {
        return $this->belongsToMany(SubOrder::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_class_id');
    }

    public function productType()
    {
        return $this->belongsToMany(
            ProductType::class,
            'dtb_product_types',
            'product_class_id',
            'product_type_config_id'
        );
    }

    public function getTypeConfigs()
    {
        return $this->belongsToMany(
            ProductTypeConfig::class,
            'dtb_product_types',
            'product_class_id',
            'product_type_config_id'
        )->selectRaw("GROUP_CONCAT(DISTINCT dtb_product_type_configs.name) as types");
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class, 'product_class_id');
    }

    public function productTypeConfigs()
    {
        return $this->belongsToMany(
            ProductTypeConfig::class,
            'dtb_product_types',
            'product_class_id',
            'product_type_config_id'
        );
    }

    //get data table dtb_product_types
    public function productTypes()
    {
        return $this->hasMany(ProductType::class, 'product_class_id', 'id');
    }

    public function getProductTypeDeleted()
    {
        return $this->productTypeConfigs()->withTrashed();
    }
}
