<?php

namespace App\Http\Requests;

use App\Enums\EnumCustomer;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $statusActive = EnumCustomer::STATUS_ACTIVE;
        $rules = [
            'name' => 'required|max:8',
            'name_furigana' => 'required|max:8',
            'surname' => 'required|max:8',
            'surname_furigana' => 'required|max:8',
            'gender' => 'required',
            'birthday' => 'required|date_format:Y-m-d|before:today',
            'email' => "email:rfc,dns|unique:dtb_customers,email,NULL,id,deleted_at,NULL,status,$statusActive,"
                . "status_signup_store,NULL",
            'postal_code' => 'required|max:7',
            'province_name' => 'required',
            'city' => 'required|max:80',
            'place' => 'required|max:80',
            'home_address' => 'nullable|max:80',
            'phone' => 'required|between:10,11|',
            'password' => 'required|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d_\W]{8,30}$/',
            'password_confirm' => 'required|same:password'
        ];
        if ($this->id) {
            $rules['email'] = 'email:rfc,dns|unique:dtb_customers,email,' .
                $this->id . ',id,deleted_at,NULL|unique:dtb_staffs,email,NULL,id,deleted_at,NULL';
        }
        return $rules;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(
            [
                'success' => false,
                'message' => 'Validation errors',
                'data' => $validator->failed()
            ],
            JsonResponse::HTTP_UNPROCESSABLE_ENTITY
        ));
    }
}
