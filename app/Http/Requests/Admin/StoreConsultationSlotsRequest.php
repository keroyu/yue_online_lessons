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
            // Optional: an unassigned slot is legal, it just falls back to the
            // support list for notifications (FR-062).
            'consultant_id' => ['nullable', 'integer', 'exists:users,id'],
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
            'consultant_id.exists'   => '找不到指定的顧問',
        ];
    }

    /**
     * Times must land on the 15-minute grid, otherwise generate() would create
     * units that no booking can ever line up with (FR-028).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // A consultant may only open their own time (FR-060). The locked
            // field in the UI is a courtesy; this is the control.
            $requested = $this->input('consultant_id');
            $user = $this->user();

            if ($requested !== null && $user && !$user->isAdmin() && (int) $requested !== $user->id) {
                $validator->errors()->add('consultant_id', '你只能將時段指派給自己');
            }

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

    /**
     * Who the new slots belong to. Defaults to whoever is creating them — that
     * is the whole point of the feature: a consultant opens their own calendar
     * without having to say so every time.
     */
    public function consultantId(): ?int
    {
        $requested = $this->input('consultant_id');

        return $requested !== null ? (int) $requested : $this->user()?->id;
    }
}
