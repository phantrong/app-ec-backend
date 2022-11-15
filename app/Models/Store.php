<?php

namespace App\Models;

use App\Enums\EnumStaff;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends CoreModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'dtb_stores';

    protected $guarded = [];

    protected $fillable = [
        'name',
        'code',
        'status',
        'description',
        'address',
        'avatar',
        'cover_image'
    ];

    /**
     * Set the store's commission.
     *
     * @param int $commission
     * @return void
     */
    public function setCommissionAttribute(?int $commission)
    {
        if (!is_null($commission)) {
            $this->attributes['commission'] = floatval($commission / 100);
        } else {
            $this->attributes['commission'] = 0.4;
        }
    }

    public function staffs(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Products::class, 'store_id', 'id');
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class, 'store_id', 'id');
    }

    // public function province(): HasOne
    // {
    //     return $this->hasOne(Province::class, 'id', 'province_id');
    // }

    // public function messages(): HasMany
    // {
    //     return $this->hasMany(Messenger::class);
    // }

    // public function stripe()
    // {
    //     return $this->hasOne(Stripe::class, 'person_stripe_id', 'acc_stripe_id');
    // }

    // public function bankHistoryCurrent()
    // {
    //     return $this->hasOne(BankHistory::class, 'id', 'bank_history_id_current');
    // }

    public function revenueOrders()
    {
        return $this->hasMany(RevenueOrder::class, 'store_id', 'id');
    }

    // public function livestreams()
    // {
    //     return $this->hasMany(LiveStream::class, 'store_id', 'id');
    // }

    public function owner()
    {
        return $this->hasOne(Staff::class, 'store_id', 'id')
            ->where('is_owner', EnumStaff::IS_OWNER);
    }

    public function subOrders()
    {
        return $this->hasMany(SubOrder::class, 'store_id', 'id');
    }
}
