<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The booking bonus codes (011 FR-031) and the self-booking notice (FR-226).
 *
 * Deliberately permissive: a code is an arbitrary marketing string, and an
 * unrecognised one never blocks a booking (D31), so there is nothing to
 * validate beyond "it fits in the column".
 */
class UpdateConsultationSettingsRequest extends FormRequest
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
            'bonus_codes' => ['nullable', 'string', 'max:500'],
            // Self-booking notice, at most a week (011 FR-226). Absent = unchanged.
            'min_notice_hours' => ['nullable', 'integer', 'min:0', 'max:168'],
        ];
    }

    public function messages(): array
    {
        return [
            'bonus_codes.max' => '優惠碼清單不能超過 500 個字',
            'min_notice_hours.integer' => '最短提前時數必須是整數',
            'min_notice_hours.min' => '最短提前時數不可為負數',
            'min_notice_hours.max' => '最短提前時數不可超過 168 小時（7 天）',
        ];
    }
}
