<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'dtb_notifications';

    protected $fillable = [
        'customer_id',
        'title',
        'content',
        'sub_order_id',
        'is_readed'
    ];
}
