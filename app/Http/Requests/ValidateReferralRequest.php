<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'referral_code' => ['required', 'string', 'max:12'],
            'buyer_email'   => ['nullable', 'email', 'max:255'],
        ];
    }
}
