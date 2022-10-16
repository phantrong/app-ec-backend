<?php

namespace App\Http\Requests;

use App\Traits\ValidationHelper;
use Illuminate\Foundation\Http\FormRequest;

class AddCartRequest extends FormRequest
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
        $rules = [
            'product_class_id' => 'required|int',
            'quantity' => 'required|int',
        ];

        if (!auth('sanctum')->check()) {
            $rules['cart_key'] = 'required|string';
        }
        return $rules;
    }
}
