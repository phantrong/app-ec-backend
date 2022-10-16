<?php

namespace App\Http\Requests;

use App\Traits\ValidationHelper;
use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
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
            'cart_item_ids' => 'required|array',
            'cart_item_ids.*' => 'integer',
            'postal_code' => 'required|digits:7',
            'receiver_name' => 'required|string',
            'receiver_name_furigana' => 'required|string',
            'phone_number' => 'required|string|max:11|min:10',
            'address_01' => 'required|string',
            'address_02' => 'required|string',
            'address_03' => 'required|string',
            'address_04' => 'nullable|string',
        ];
        if (!auth('sanctum')->check()) {
            $rules['cart_key'] = 'required|string';
        }
        return $rules;
    }
}
