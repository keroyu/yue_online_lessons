<?php

namespace App\Http\Requests\Admin;

use App\Models\ConsultationSlot;
use App\Services\ConsultationSlotService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * Moving a confirmed booking to another slot (011 US14 / FR-048).
 *
 * Takes `date` + `start_time` rather than one timestamp, matching
 * StoreConsultationSlotsRequest: the grid speaks in Taipei wall-clock, and
 * keeping the conversion on this side means the Vue component never has to
 * know what timezone the business runs in (D32).
 *
 * Authorisation is the staff route group's job.
 */
class RescheduleBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date'       => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required'          => '請選擇日期',
            'start_time.required'    => '請選擇新的開始時間',
            'start_time.date_format' => '開始時間格式須為 HH:MM',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $time = (string) $this->input('start_time');

            if (preg_match('/^\d{2}:(\d{2})$/', $time, $m)
                && (int) $m[1] % ConsultationSlot::UNIT_MINUTES !== 0) {
                $validator->errors()->add('start_time', '時間須為 15 分鐘的整數倍（00 / 15 / 30 / 45）');

                return;
            }

            // Rescheduling into the past would send a calendar invite for a
            // moment that has already gone by.
            if ($validator->errors()->isEmpty() && $this->startsAt()->isPast()) {
                $validator->errors()->add('start_time', '新時段不能是過去的時間');
            }
        });
    }

    /** The requested start, read as Taipei wall-clock (比照 ConsultationSlotController::range). */
    public function startsAt(): Carbon
    {
        return Carbon::parse(
            $this->input('date') . ' ' . $this->input('start_time'),
            ConsultationSlotService::DISPLAY_TZ
        );
    }
}
