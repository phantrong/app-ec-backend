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
        $rules = [
            'store.name' => 'required|max:80',
            'store.address' => 'max:80',
            'store.description' => 'max:200',
            'owner.name' => 'required|max:80',
            'owner.phone' => 'regex:/^[0-9]{10,11}$/',
            'owner.email' => 'required|max:255|unique:dtb_staffs,email,NULL,id,deleted_at,NULL',
            'owner.password' => 'required|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d_\W]{8,30}$/',
            'owner.password_confirm' => 'required|same:owner.password'
        ];

        return $rules;
    }
}
