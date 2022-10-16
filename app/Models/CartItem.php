<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class CartItem extends CoreModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'dtb_cart_items';

    protected $fillable = [
        'cart_id',
        'product_classes_id',
        'quantity'
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function productClassItem()
    {
        return $this->belongsTo(ProductClass::class, 'product_classes_id', 'id');
    }
}
