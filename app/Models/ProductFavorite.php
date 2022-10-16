<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductFavorite extends CoreModel
{
    use HasFactory;

    protected $table = 'dtb_product_favorites';

    protected $fillable = [
        'customer_id', 'product_id'
    ];
}
