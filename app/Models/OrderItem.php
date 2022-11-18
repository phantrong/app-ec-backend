<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends CoreModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'dtb_order_items';

    protected $hidden = ['pivot'];

    protected $fillable = [
        'sub_order_id',
        'price',
        'quantity',
        'cart_item_id'
    ];

    public function subOrder()
    {
        return $this->belongsTo(SubOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Products::class);
    }
}
