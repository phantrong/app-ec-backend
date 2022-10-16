<?php

namespace App\Http\Requests;

use App\Enums\EnumGender;
use App\Enums\EnumStaff;
use App\Traits\ValidationHelper;
use Illuminate\Foundation\Http\FormRequest;

class StaffRequest extends FormRequest
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
        return [
            'name' => 'required|max:40|regex:/^[a-zA-Z\d\sぁ-んァ-ン・ーヽヾ、一-龥]+$/',
            'phone' => 'required|regex:/^[\d]{10,11}+$/',
            'gender' => 'required|in:' . EnumGender::GENDER_FEMALE . ',' . EnumGender::GENDER_MALE
            . ',' . EnumGender::GENDER_OTHER,
            'address' => 'required|max:200',
            'email' => [
                'required',
                'max:255',
                'email:rfc,dns',
                "unique:dtb_staffs,email,{$this->id},id,deleted_at,NULL",
                "unique:dtb_customers,email,{$this->customer_id},id,deleted_at,NULL,store_id,NOT_NULL"
            ],
            'status' => 'nullable|in:' . EnumStaff::STATUS_ACCESS . ',' . EnumStaff::STATUS_BLOCKED,
        ];
    }
}
