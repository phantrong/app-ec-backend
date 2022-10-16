<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalendarStaff extends CoreModel
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'dtb_calendar_staff';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'staff_id',
        'reception_date',
        'reception_start_time',
        'reception_end_time',
        'is_booked',
    ];

    /**
     * Get the calendar_staff's reception date value.
     *
     * @return Attribute
     */
    protected function receptionDateValue(): Attribute
    {
        return Attribute::make(
            get: fn () => Carbon::parse($this->reception_date)->format('Y/m/d'),
        );
    }

    /**
     * Get the calendar_staff's reception start time value.
     *
     * @return Attribute
     */
    protected function receptionStartTimeValue(): Attribute
    {
        return Attribute::make(
            get: fn () => Carbon::parse($this->reception_start_time)->format('H:i'),
        );
    }
}
