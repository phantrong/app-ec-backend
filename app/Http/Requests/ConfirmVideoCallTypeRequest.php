<?php

namespace App\Http\Requests;

use App\Enums\EnumVideoCallType;
use App\Traits\ValidationHelper;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmVideoCallTypeRequest extends FormRequest
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
        $videoCallTypeArr = [
            EnumVideoCallType::TYPE_PUBLIC,
            EnumVideoCallType::TYPE_PRIVATE,
        ];
        $videoCallTypeStr = implode(',', $videoCallTypeArr);

        return [
            'video_call_type' => "required|numeric|in:$videoCallTypeStr",
        ];
    }
}
