<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerAddress extends CoreModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'dtb_customer_addresses';

    protected $guarded = [];
}
