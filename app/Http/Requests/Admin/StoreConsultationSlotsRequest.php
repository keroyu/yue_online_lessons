<?php

namespace App\Http\Requests\Admin;

use App\Models\ConsultationSlot;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationSlotsRequest extends FormRequest
{
    /**
     * Staff middleware handles authorization.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date'       => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required'          => '請選擇日期',
            'date.after_or_equal'    => '日期不能早於今天',
            'start_time.required'    => '請填寫開始時間',
            'start_time.date_format' => '開始時間格式須為 HH:MM',
            'end_time.required'      => '請填寫結束時間',
            'end_time.date_format'   => '結束時間格式須為 HH:MM',
            'end_time.after'         => '結束時間須晚於開始時間',
        ];
    }

    /**
     * Times must land on the 15-minute grid, otherwise generate() would create
     * units that no booking can ever line up with (FR-028).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach (['start_time', 'end_time'] as $field) {
                $value = (string) $this->input($field);

                if (!preg_match('/^\d{2}:(\d{2})$/', $value, $m)) {
                    continue;
                }

                if ((int) $m[1] % ConsultationSlot::UNIT_MINUTES !== 0) {
                    $validator->errors()->add($field, '時間須為 15 分鐘的整數倍（00 / 15 / 30 / 45）');
                }
            }
        });
    }
}
