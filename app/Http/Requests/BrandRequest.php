<?php

namespace App\Http\Requests;

use App\Enums\EnumBrand;
use App\Traits\ValidationHelper;
use Illuminate\Foundation\Http\FormRequest;

class BrandRequest extends FormRequest
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
        $status = [EnumBrand::STATUS_PRIVATE, EnumBrand::STATUS_PUBLIC];
        $id = $this->id ? $this->id : 'null';
        return [
            'name' => 'required|max:20|unique:mtb_brands,name,' .
                $id . ',id,category_id,' . $this->category_id.',deleted_at,NULL',
            'status' => 'required|in:' . implode(',', $status),
            'category_id' => 'required|exists:mtb_categories,id,deleted_at,NULL'
        ];
    }
}
