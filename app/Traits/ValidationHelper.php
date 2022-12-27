<?php

namespace App\Traits;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

trait ValidationHelper
{
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => 'Thông tin nhập chưa chính xác',
            'data'      => $validator->failed()
        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));
    }
}
