<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends CoreModel
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'dtb_bookings';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]|bool
     */
    protected $guarded = [];
}
