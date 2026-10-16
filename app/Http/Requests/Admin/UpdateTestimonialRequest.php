<?php

namespace App\Http\Requests\Admin;

use App\Models\Testimonial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** 011 FR-244 / FR-249 — show or hide a testimonial, or correct its identity. */
class UpdateTestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canAccessSalesPanel();
    }

    public function rules(): array
    {
        return [
            'status'   => ['required_without:identity', Rule::in(['published', 'hidden'])],
            'identity' => ['required_without:status', Rule::in(array_keys(Testimonial::IDENTITY_LABELS))],
        ];
    }
}
