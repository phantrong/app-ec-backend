<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Brand extends CoreModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'mtb_brands';

    protected $fillable = [
        'name', 'status', 'order_no', 'category_id'
    ];

    public function products(): HasMany
    {
        return $this->hasMany('App\Models\Products', 'brand_id', 'id');
    }
}
