<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductMedia extends CoreModel
{
    use HasFactory, SoftDeletes;

    const MEDIA_IMAGE = 1;
    const MEDIA_VIDEO = 2;

    protected $table = 'dtb_product_medias';

    protected $hidden = ['pivot'];

    protected $fillable = [
        'product_id', 'media_type', 'media_path'
    ];

    public function product()
    {
        return $this->belongsTo(Products::class);
    }
}
