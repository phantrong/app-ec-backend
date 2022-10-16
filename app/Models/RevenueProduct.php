<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class RevenueProduct extends CoreModel
{
    use HasFactory;

    protected $table = 'dtb_revenue_products';

    public $timestamps = false;
}
