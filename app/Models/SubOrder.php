<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubOrder extends CoreModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'dtb_sub_orders';

    protected $hidden = ['pivot'];

    protected $fillable = [
        'code',
        'sub_order_code',
        'status',
        'order_id',
        'store_id',
        'total',
        'discount',
        'commission',
        'total_payment',
        'verified_at',
        'completed_at',
        'canceled_at',
        'note'
    ];

    public function products()
    {
        return $this->belongsToMany(Products::class, 'dtb_order_items', 'sub_order_id', 'product_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'sub_order_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
