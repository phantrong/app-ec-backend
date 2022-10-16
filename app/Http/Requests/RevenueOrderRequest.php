<?php

namespace App\Http\Requests;

use App\Enums\EnumSubOrder;
use App\Traits\ValidationHelper;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class RevenueOrderRequest extends FormRequest
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
        $rules = [];
        $rules['type'] = 'nullable|numeric';
        if ($this->start_date || $this->end_date) {
            $rules['type'] = 'required|numeric';
        }
        if ($this->start_date && $this->end_date && $this->type ==  EnumSubOrder::UNIT_DAY) {
            $startDate = Carbon::parse($this->start_date)
                ->addDays(EnumSubOrder::MAX_DAY_FILTER)
                ->format('Y-m-d');
            $rules['end_date'] = "before:$startDate";
        }
        return $rules;
    }
}
