<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stripe extends CoreModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'dtb_stripes';

    protected $guarded = [];

    protected $appends = ['first_name', 'last_name', 'first_name_furigana', 'last_name_furigana'];

    // rename column first_name to surname
    protected function firstName(): Attribute
    {
        return new Attribute(
            get: fn ($value, $attributes) => isset($attributes['surname']) ? $attributes['surname'] : null,
        );
    }

    // rename column last_name to name
    protected function lastName(): Attribute
    {
        return new Attribute(
            get: fn ($value, $attributes) => isset($attributes['name']) ? $attributes['name'] : null,
        );
    }

    // rename column first_name_furigana to surname_furigana
    protected function firstNameFurigana(): Attribute
    {
        return new Attribute(
            get: fn ($value, $attributes) =>
            isset($attributes['surname_furigana']) ? $attributes['surname_furigana'] : null,
        );
    }

    // rename column last_name_furigana to name_furigana
    protected function lastNameFurigana(): Attribute
    {
        return new Attribute(
            get: fn ($value, $attributes) => isset($attributes['name_furigana']) ? $attributes['name_furigana'] : null,
        );
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'id', 'customer_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }
}
