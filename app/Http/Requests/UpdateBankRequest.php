<?php

namespace App\Http\Requests;

use App\Enums\EnumBankHistory;
use App\Traits\ValidationHelper;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBankRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        $arrayType = [EnumBankHistory::TYPE_INDIVIDUAL, EnumBankHistory::TYPE_COMPANY];
        return [
            'bank_id' => 'required',
            'branch_id' => 'required',
            'type' => 'required|in:' . implode(',', $arrayType),
            'bank_number' => 'required|regex:/^[0-9]{7,8}$/',
            'customer_name' => 'required|max:30',
        ];
    }
}
