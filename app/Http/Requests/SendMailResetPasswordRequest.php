<?php

namespace App\Http\Requests;

use App\Enums\EnumStaff;
use App\Traits\ValidationHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendMailResetPasswordRequest extends FormRequest
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
            'email' => [
                'required', 'email', 'exists:dtb_staffs,email,status,' .
                EnumStaff::STATUS_ACCESS . ',deleted_at,NULL'
            ]
        ];
    }
}
