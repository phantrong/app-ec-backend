<?php

namespace App\Http\Requests;

use App\Enums\EnumStore;
use App\Models\Store;
use App\Traits\ValidationHelper;
use Illuminate\Foundation\Http\FormRequest;

class CheckBookingRequest extends FormRequest
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
        $tblStore = Store::getTableName();

        return [
            'reception_date' => 'required|date_format:"Y-m-d"',
            'reception_time' => 'required|date_format:"H:i"',
            'booking_id' => "nullable|numeric",
            'store_id' => "required|exists:$tblStore,id,deleted_at,NULL,status," . EnumStore::STATUS_CONFIRMED,
        ];
    }
}
