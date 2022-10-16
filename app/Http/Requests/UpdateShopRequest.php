<?php

namespace App\Http\Requests;

use App\Traits\ValidationHelper;
use Illuminate\Foundation\Http\FormRequest;

class UpdateShopRequest extends FormRequest
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
            'store.company' => 'required|max:80|regex:/^[a-zA-Zぁ-んァ-ン・ーヽヾ、一-龥]+$/',
            'store.name' => 'required|max:80|regex:/^[a-zA-Z\dぁ-んァ-ン・ーヽヾ、一-龥]+$/',
            'store.postal_code' => 'required|regex:/^[0-9]{1,7}$/',
            'store.address' => 'max:80',
            'store.phone' => 'required|regex:/^[0-9]{10,11}$/',
            'store.fax' => 'nullable|regex:/^[0-9+-]{1,20}$/',
            'store.link' => 'nullable',
            'store.work_day' => 'required|',
            'store.description' => 'max:200',
            'stripe.first_name' => 'required|max:8|regex:/^[a-zA-Zぁ-んァ-ン・ーヽヾ、一-龥]+$/',
            'stripe.last_name' => 'required|max:8|regex:/^[a-zA-Zぁ-んァ-ン・ーヽヾ、一-龥]+$/',
            'stripe.first_name_furigana' => 'required|max:8',
            'stripe.last_name_furigana' => 'required|max:8',
            'avatar' => 'nullable|mimes:'.config('filesystems.image_extension').
                "|max:".config('filesystems.avatar_size'),
            'cover_image' => 'nullable|mimes:'.config('filesystems.image_extension').
                "|max:".config('filesystems.avatar_size'),
            'image_card_first' => 'nullable|mimes:'.config('filesystems.image_extension').
                "|max:".config('filesystems.avatar_size'),
            'image_card_second' => 'nullable|mimes:'.config('filesystems.image_extension').
                "|max:".config('filesystems.avatar_size')
        ];
    }
}
