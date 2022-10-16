<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductTypeConfig extends CoreModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'dtb_product_type_configs';

    protected $hidden = ['pivot'];

    protected $fillable = [
        'product_id', 'type', 'type_name', 'name'
    ];

    public function productClasses()
    {
        return $this->belongsToMany(ProductClass::class);
    }
}
