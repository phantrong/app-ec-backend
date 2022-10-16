<?php

namespace App\Http\Requests;

use App\Enums\EnumStore;
use App\Enums\EnumVideoCallType;
use App\Models\Booking;
use App\Models\CalendarStaff;
use App\Models\Store;
use App\Traits\ValidationHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateBookingRequest extends FormRequest
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
        $tblBooking = Booking::getTableName();
        $tblStore = Store::getTableName();

        return [
            'old_booking_id' => "required|exists:$tblBooking,id,deleted_at,NULL",
            'store_id' => "required|exists:$tblStore,id,deleted_at,NULL,status," . EnumStore::STATUS_CONFIRMED,
            'reception_date' => 'required|date_format:"Y-m-d"|after_or_equal:' . now()->format('Y-m-d'),
            'reception_time' => 'required|date_format:"H:i"',
            'video_call_type' => [
                'nullable',
                'in:' . EnumVideoCallType::TYPE_PUBLIC . ',' . EnumVideoCallType::TYPE_PRIVATE,
            ],
            'note' => 'nullable|max:3000'
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
                $this->checkTodayReceptionTime($validator);
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
        $receptionDatetime = "$this->reception_date $this->reception_time";
        $dateTimeNow = date('Y-m-d H:i');
        if ($receptionDatetime < $dateTimeNow) {
            $validator->addFailure("reception_datetime", 'AfterOrEqualNow', [$dateTimeNow]);
        }
    }
}
