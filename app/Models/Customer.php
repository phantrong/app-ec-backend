<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasFactory, SoftDeletes, Notifiable, HasApiTokens;

    protected $table = 'dtb_customers';

    protected $fillable = [
        'status',
        'name',
        'email',
        'password',
        'verify_content',
        'phone',
        'gender',
        'birthday',
        'avatar',
        'send_mail',
        'address'
    ];

    protected $hidden = ['password', 'verify_content'];

    public static function getTableName(): string
    {
        return with(new static)->getTable();
    }

    public function address()
    {
        return $this->hasOne(CustomerAddress::class, 'customer_id', 'id');
    }

    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'store_id');
    }

    public function stripe()
    {
        return $this->hasMany(Stripe::class, 'customer_id', 'id')
            ->orderBy('created_at', 'desc')
            ->limit(1);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Messenger::class);
    }
}
