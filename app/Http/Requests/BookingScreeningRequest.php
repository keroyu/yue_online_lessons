<?php

namespace App\Http\Requests;

use App\Support\BookingScreening;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Step 1 of the wizard: identity plus the five qualifying answers (011 US24).
 *
 * All five are required. They read as optional questions, but under a scoring
 * gate an unanswered question can only count as zero — leaving them optional
 * would make a skipped question silently subtract, and nobody would ever learn
 * what stopped them (FR-124).
 *
 * The scope acknowledgement is required here and nowhere else (FR-162…FR-165).
 */
class BookingScreeningRequest extends FormRequest
{
    /** Public endpoint — the course decides eligibility, not the visitor. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge([
            'name'  => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            // The scope acknowledgement (FR-163). Validated but never stored:
            // it is a gate that makes unsuitable applicants leave on their own,
            // not a consent record.
            //
            // Deliberately NOT in BookingScreening::rules() (FR-164): that set
            // is shared with the booking submit, where a resumed draft and every
            // pre-feature lead arrives having never seen this checkbox.
            'screen_ack' => ['accepted'],
        ], BookingScreening::rules());
    }

    public function messages(): array
    {
        $messages = [
            'name.required'      => '請填寫暱稱',
            'email.required'     => '請填寫 Email',
            'email.email'        => 'Email 格式不正確',
            'screen_ack.accepted' => '請先勾選上方的服務範圍告知',
        ];

        // One wording for all five: the form marks every question, so naming
        // which one is missing adds nothing the red border did not already say.
        foreach (BookingScreening::fields() as $field) {
            $messages["{$field}.required"] = '請回答這一題';
            $messages["{$field}.in"]       = '選項不正確，請重新選擇';
        }

        return $messages;
    }
}
