<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shipping extends CoreModel
{
    use HasFactory;

    protected $table = 'dtb_shippings';

    protected $hidden = ['pivot'];

    protected $fillable = [
        'order_id',
        'email',
        'postal_code',
        'receiver_name',
        'phone_number',
        'address',
        'receiver_name_furigana'
    ];

    public function order()
    {
        return $this->hasOne(Shipping::class);
    }
}
