<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class Staff extends Authenticatable
{
    use HasFactory, SoftDeletes, HasApiTokens;

    public $preventAttrSet = true;

    protected $table = 'dtb_staffs';

    protected $guard = 'store';

    protected $fillable = [
        'phone',
        'status',
        'email',
        'password',
        'store_id',
        'is_owner',
        'name'
    ];

    protected $hidden = ['password'];

    public static function getTableName(): string
    {
        return with(new static)->getTable();
    }

    /**
     * Set the staff's password.
     *
     * @param string $password
     * @return void
     */
    public function setPasswordAttribute(?string $password)
    {
        if (!is_null($password) && $this->preventAttrSet) {
            $this->attributes['password'] = Hash::make($password);
        } else {
            $this->attributes['password'] = $password;
        }
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function calendar()
    {
        return $this->hasMany(CalendarStaff::class);
    }

    public function livestream()
    {
        return $this->hasMany(LiveStream::class);
    }
}
