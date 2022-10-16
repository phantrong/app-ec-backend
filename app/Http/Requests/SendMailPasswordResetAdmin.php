<?php

namespace App\Http\Requests;

use App\Traits\ValidationHelper;
use Illuminate\Foundation\Http\FormRequest;

class SendMailPasswordResetAdmin extends FormRequest
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
            'email' => 'required|exists:dtb_admins,email,deleted_at,NULL'
        ];
    }
}
