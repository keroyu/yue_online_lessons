<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A self-booked consultation slot (011 US38 / FR-227). Ownership and
 * availability are the service's job; this only checks the shape.
 */
class StoreConsultationBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'starts_at' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'starts_at.required' => '請選擇時段',
            'starts_at.date'     => '時段格式不正確',
        ];
    }
}
