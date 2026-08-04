<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The booking bonus codes (011 FR-031).
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
        ];
    }

    public function messages(): array
    {
        return [
            'bonus_codes.max' => '優惠碼清單不能超過 500 個字',
        ];
    }
}
