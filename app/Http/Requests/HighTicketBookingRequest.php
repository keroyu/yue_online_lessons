<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The application form behind the raised booking bar (011 US9 / FR-026 / FR-027).
 *
 * The commitment checklist is validated here as well as in the wizard: the
 * button being disabled is a courtesy, not a control.
 */
class HighTicketBookingRequest extends FormRequest
{
    /** Public booking endpoint — the course itself decides eligibility. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:100'],
            'email'          => ['required', 'email', 'max:255'],
            'phone'          => ['required', 'string', 'max:30'],
            'occupation'     => ['required', 'string', 'max:255'],
            'bottleneck'     => ['required', 'string', 'max:2000'],
            'expertise'      => ['required', 'string', 'max:2000'],
            // Scheme-restricted: the bare `url` rule accepts ftp:, javascript:
            // and data:, and this string ends up as an href in the admin panel.
            'social_url'     => ['nullable', 'url:http,https', 'max:500'],
            'commitments'    => ['required', 'array', 'size:5'],
            // Optional on the waitlist route, where there is nothing to pick.
            'slot_starts_at' => [$this->routeIs('course.waitlist') ? 'nullable' : 'required', 'date'],
            'code'           => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'           => '請填寫暱稱',
            'email.required'          => '請填寫 Email',
            'email.email'             => 'Email 格式不正確',
            'phone.required'          => '請填寫手機電話',
            'occupation.required'     => '請填寫職業和從事時長',
            'bottleneck.required'     => '請填寫目前的事業瓶頸',
            'expertise.required'      => '請填寫你的知識或能力專長',
            'social_url.url'          => '社群網址須為完整網址，並以 http:// 或 https:// 開頭',
            'social_url.max'          => '社群網址不能超過 500 個字',
            'commitments.required'    => '請確認全部的預約前提條件',
            'commitments.size'        => '請確認全部的預約前提條件',
            'slot_starts_at.required' => '請選擇諮詢時段',
            'slot_starts_at.date'     => '時段格式不正確，請重新選擇',
        ];
    }

    /**
     * All five must be ticked — a partially agreed checklist is the same as no
     * checklist at all (FR-026).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $commitments = $this->input('commitments');

            if (!is_array($commitments)) {
                return;
            }

            foreach ($commitments as $checked) {
                if ($checked !== true && $checked !== 'true' && $checked !== 1 && $checked !== '1') {
                    $validator->errors()->add('commitments', '請確認全部的預約前提條件');

                    return;
                }
            }
        });
    }
}
