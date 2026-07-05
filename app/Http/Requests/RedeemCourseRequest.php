<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RedeemCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is behind auth middleware; redemption acts on the authenticated user.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // Course comes from the route binding; the user from auth. No body input.
        return [];
    }
}
