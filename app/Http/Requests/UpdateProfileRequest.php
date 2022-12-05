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
            'name' => 'required|max:8',
            'name_furigana' => 'required|max:8',
            'surname' => 'required|max:8',
            'surname_furigana' => 'required|max:8',
            'gender' => 'required',
            'birthday' => 'required|before:today',
        ];
        if ($this->avatar) {
            $rules['avatar'] = 'nullable|mimes:' . config('filesystems.image_extension') .
                "|max:" . config('filesystems.avatar_size');
        }
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
