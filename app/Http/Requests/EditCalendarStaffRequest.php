<?php

namespace App\Http\Requests;

use App\Enums\EnumBookingStatus;
use App\Enums\EnumStaff;
use App\Models\Booking;
use App\Models\CalendarStaff;
use App\Traits\ValidationHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class EditCalendarStaffRequest extends FormRequest
{
    use ValidationHelper;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $storeId = Auth::user()->store_id;
        $accessedStaffStatus = EnumStaff::STATUS_ACCESS;

        return [
            'staff_id' => [
                'required',
                "exists:dtb_staffs,id,deleted_at,NULL,status,$accessedStaffStatus,store_id,$storeId"
            ],
            'reception_date' => 'required|date_format:"Y-m-d"|after_or_equal:' . now()->format('Y-m-d'),
            'reception_times' => 'array',
            'reception_times.*' => 'date_format:"H:i"',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  Validator $validator
     *
     * @return void
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            if (!$validator->failed()) {
                $this->checkBookedReceptionTime($validator);
                if (!empty($this->reception_times)) {
                    $this->checkTodayReceptionTime($validator);
                }
            }
        });
    }

    /**
     * Check if reception time inputs are future.
     *
     * @param $validator
     */
    private function checkTodayReceptionTime(Validator $validator)
    {
        $calendarStaffList = CalendarStaff::where('staff_id', $this->staff_id)
            ->whereDate('reception_date', $this->reception_date)
            ->get('reception_start_time');
        $bookedReceptionTimeArr = $calendarStaffList->pluck('reception_start_time_value')->toArray();

        $receptionTimeInputArr = [];
        if ($this->reception_date == date('Y-m-d')) {
            foreach ($this->reception_times as $receptionTime) {
                $receptionTimeInputArr[] = $receptionTime;
            }
        }

        $timeNow = date('H:i');
        $receptionDateTimeFutureArr = [];
        foreach ($receptionTimeInputArr as $receptionTimeInput) {
            if ($receptionTimeInput < $timeNow && !in_array($receptionTimeInput, $bookedReceptionTimeArr)) {
                $receptionDateTimeFutureArr[] = "$this->reception_date $receptionTimeInput";
            }
        }

        if (!empty($receptionDateTimeFutureArr)) {
            $validator->addFailure("reception_datetime", 'AfterOrEqualNow', $receptionDateTimeFutureArr);
        }
    }

    /**
     * Check if reception time inputs have booked reception time.
     *
     * @param $validator
     */
    private function checkBookedReceptionTime($validator)
    {
        $tblCalendarStaff = CalendarStaff::getTableName();
        $tblBooking = Booking::getTableName();

        // Get calendar_staff list which have booked reception time
        $calendarStaffList = CalendarStaff::join(
            $tblBooking,
            "$tblCalendarStaff.id",
            '=',
            "$tblBooking.calendar_staff_id"
        )
            ->where('staff_id', $this->staff_id)
            ->whereDate('reception_date', $this->reception_date)
            ->whereIn("$tblBooking.status", EnumBookingStatus::BOOKED_BOOKING_STATUS)
            ->whereNull("$tblBooking.deleted_at")
            ->get('reception_start_time');

        $receptionDateTimeBookedArr = [];
        if (!$calendarStaffList->isEmpty()) {
            $receptionTimeArr = $calendarStaffList->pluck('reception_start_time_value')->toArray();
            if (!empty($receptionTimeArr)) {
                $receptionDateTimeBookedArr = $this->addBookedReceptionTime($receptionTimeArr);
            }
        }

        if ($receptionDateTimeBookedArr) {
            $validator->addFailure("reception_datetime", 'Booked', $receptionDateTimeBookedArr);
        }
    }

    /**
     * Add booked reception time.
     *
     * @param $receptionTimeArr
     * @return array
     */
    private function addBookedReceptionTime($receptionTimeArr)
    {
        foreach ($receptionTimeArr as $bookedReceptionTime) {
            if (!in_array($bookedReceptionTime, $this->reception_times)) {
                $receptionDateTimeBookedArr[] = $bookedReceptionTime;
            }
        }

        return $receptionDateTimeBookedArr ?? [];
    }
}
