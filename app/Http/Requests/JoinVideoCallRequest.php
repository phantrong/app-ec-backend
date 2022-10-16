<?php

namespace App\Http\Requests;

use App\Enums\EnumStore;
use App\Models\Store;
use App\Traits\ValidationHelper;
use Illuminate\Foundation\Http\FormRequest;

class JoinVideoCallRequest extends FormRequest
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
        $tblStore = Store::getTableName();

        return [
            'store_id' => "required|exists:$tblStore,id,deleted_at,NULL,status," . EnumStore::STATUS_CONFIRMED,
        ];
    }
}
