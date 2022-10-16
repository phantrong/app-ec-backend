<?php

namespace App\Http\Requests;

use App\Enums\EnumCustomer;
use App\Traits\ValidationHelper;
use Illuminate\Foundation\Http\FormRequest;

class SendMailSignUpStoreRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        $statusUpgradeCreate = EnumCustomer::STATUS_SIGNUP_NEW;
        $statusCreate = EnumCustomer::STATUS_CREATE;
        return [
            'email' => [
                'required',
                'max:255',
                'email:rfc,dns',
                "unique:dtb_staffs,email,NULL,id,deleted_at,NULL",
                "unique:dtb_customers,email,NULL,id,deleted_at,NULL,store_id,NOT_NULL,"
                    . "status_signup_store,$statusUpgradeCreate,status,$statusCreate"
            ]
        ];
    }
}
