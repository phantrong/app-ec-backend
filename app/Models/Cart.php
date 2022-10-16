<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cart extends CoreModel
{
    use HasFactory;

    protected $table = 'dtb_carts';

    protected $fillable = [
        'cart_key',
        'customer_id'
    ];

    public function cartItem()
    {
        return $this->hasMany(CartItem::class, 'cart_id', 'id');
    }
}
