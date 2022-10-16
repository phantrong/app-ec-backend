<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends CoreModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'dtb_orders';

    protected $hidden = ['pivot'];

    protected $fillable = [
        'order_code',
        'status',
        'total',
        'discount',
        'total_payment',
        'ordered_at',
        'customer_id',
        'stripe_session_id',
    ];

    public function shipping()
    {
        return $this->hasOne(Shipping::class);
    }

    public function subOrders()
    {
        return $this->hasMany(SubOrder::class, 'order_id');
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'id', 'customer_id');
    }
}
