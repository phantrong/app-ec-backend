<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends CoreModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'mtb_categories';

    protected $fillable = [
        'name', 'status', 'order_no', 'image_path'
    ];

    public function products(): HasMany
    {
        return $this->hasMany('App\Models\Products', 'category_id', 'id');
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class, 'category_id', 'id');
    }
}
