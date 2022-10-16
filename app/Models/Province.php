<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends CoreModel
{
    use HasFactory;

    protected $table = 'mtb_provinces';

    public function stores(): HasMany
    {
        return $this->hasMany('App\Models\Store', 'province_id', 'id');
    }
}
