<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class UpdateProfileRequest extends FormRequest
{
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
            'name' => 'required|max:255',
            'gender' => 'required',
            'birthday' => 'required|date_format:Y-m-d|before:today',
            'phone' => 'required|between:10,11'
        ];
        return $rules;
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Các giá trị nhập chưa hợp lệ. Vui lòng nhập lại.',
            'data' => $validator->failed()
        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));
    }
}
