<?php

namespace App\Http\Requests;

use App\Enums\EnumProduct;
use App\Traits\ValidationHelper;
use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
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
        $storeId = $this->user()->store_id;
        $rules = [
            'name' => 'required|max:100|unique:dtb_products,name,NULL,id,store_id,' . $storeId . ',deleted_at,NULL',
            'category_id' => 'required|exists:mtb_categories,id',
            'price' => 'required|numeric|min:' . config('database.product_min_price') .
                '|max:' . config('database.product_max_price'),
            'discount' => 'required|numeric|min:' . config('database.product_min_price') .
                '|max:' . config('database.product_max_price'),
            // 'stock' => 'required|numeric|max:' . config('database.product_max_stock'),
            'product_medias' => 'required',
            'description' => 'required',
            // 'status' => 'required|in:' . EnumProduct::STATUS_PUBLIC . ',' . EnumProduct::STATUS_NO_PUBLIC
        ];
        if ($this->id) {
            return [
                'name' => "required|max:100|unique:dtb_products,name,$this->id,id,store_id,$storeId,deleted_at,NULL",
                'category_id' => 'required|exists:mtb_categories,id',
                'price' => 'required|numeric|min:' . config('database.product_min_price') .
                    '|max:' . config('database.product_max_price'),
                'discount' => 'required|numeric|min:' . config('database.product_min_price') .
                    '|max:' . config('database.product_max_price'),
                'product_medias' => 'required',
                // 'stock' => 'required|numeric|max:' . config('database.product_max_stock'),
                'description' => 'required',
                // 'status' => 'required|in:' . EnumProduct::STATUS_PUBLIC . ',' . EnumProduct::STATUS_NO_PUBLIC
            ];
        }
        return $rules;
    }
}
