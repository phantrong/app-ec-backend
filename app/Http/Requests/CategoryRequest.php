<?php

namespace App\Http\Requests;

use App\Traits\ValidationHelper;
use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
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
        $id = $this->id ? $this->id : 'null';
        $rules = [
            'image' => 'required|mimes:' . config('filesystems.image_extension') .
                "|max:" . config('filesystems.avatar_size'),
            'name' => 'required|max:20|unique:mtb_categories,name,' . $id . ',id,deleted_at,NULL',
            'status' => 'required'
        ];
        if ($this->id) {
            $rules['image'] = '';
        }
        return $rules;
    }
}
