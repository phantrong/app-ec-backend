<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductType extends CoreModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'dtb_product_types';

    protected $fillable = [
        'product_type_config_id', 'product_class_id'
    ];

    public function productTypeConfig()
    {
        return $this->belongsTo(ProductTypeConfig::class, 'product_type_config_id', 'id');
    }
}
