<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class LiveStream extends CoreModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'dtb_live_streams';

    protected $guarded = [];

    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class, 'id', 'staff_id');
    }

    public function store(): HasOne
    {
        return $this->hasOne(Store::class, 'id', 'store_id');
    }
}
