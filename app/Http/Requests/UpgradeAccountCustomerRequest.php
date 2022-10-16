<?php

namespace App\Http\Requests;

use App\Enums\EnumCustomer;
use App\Enums\EnumStripe;
use App\Traits\ValidationHelper;
use Illuminate\Foundation\Http\FormRequest;

class UpgradeAccountCustomerRequest extends FormRequest
{
    use ValidationHelper;

    const YEAR_MIN = 13;

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
        $gender = [EnumCustomer::GENDER_MALE, EnumCustomer::GENDER_FEMALE, EnumCustomer::GENDER_UN_KNOWN];
        $statusUpgradeCreate = EnumCustomer::STATUS_SIGNUP_NEW;
        $statusCreate = EnumCustomer::STATUS_CREATE;
        $time = now()->subYear(self::YEAR_MIN)->format('Y-m-d');
        $rules = [
            'store.company' => 'required|max:80',
            'store.name' => 'required|max:80',
            'store.postal_code' => 'required|max:7',
            'store.province_id' => 'required|exists:mtb_provinces,id',
            'store.city' => 'required|max:80',
            'store.place' => 'required|max:80',
            'store.address' => 'max:80',
            'store.phone' => 'regex:/^[0-9]{10,11}$/',
            'store.fax' => 'nullable|regex:/^[0-9+-]{5,20}$/',
            'store.description' => 'max:200',
            'stripe.first_name' => 'required|max:8',
            'stripe.last_name' => 'required|max:8',
            'stripe.first_name_furigana' => 'required|max:8',
            'stripe.last_name_furigana' => 'required|max:8',
            'stripe.gender' => 'required|in:' . implode(',', $gender),
            'stripe.position' => 'required|max:80',
            'stripe.birthday' => "required|before_or_equal:$time",
            'stripe.phone' => 'required|regex:/^[0-9]{10,11}$/',
            'stripe.image_type' => 'required|in:' . implode(',', EnumStripe::ARRAY_IMAGE),
            'stripe.postal_code' => 'required|max:7',
            'stripe.province_id' => 'required|exists:mtb_provinces,id',
            'stripe.city' => 'required|max:80',
            'stripe.place' => 'required|max:80',
            'image_card_first' => 'nullable|mimes:' . config('filesystems.image_extension') .
                "|max:" . config('filesystems.avatar_size'),
            'image_card_second' => 'nullable|mimes:' . config('filesystems.image_extension') .
                "|max:" . config('filesystems.avatar_size')
        ];

        if ($this->is_sign_up_store) {
            $rules['email'] = [
                'required',
                'max:255',
                'email:rfc,dns',
                "unique:dtb_staffs,email,NULL,id,deleted_at,NULL",
                "unique:dtb_customers,email,NULL,id,deleted_at,NULL,store_id,NOT_NULL,"
                    . "status_signup_store,$statusUpgradeCreate,status,$statusCreate"
            ];
        }

        return $rules;
    }
}
