<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GrantPointsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'note'   => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => '請輸入派發點數',
            'amount.integer'  => '派發點數需為整數',
            'amount.min'      => '派發點數需為正整數',
            'note.max'        => '備註不能超過 255 字',
        ];
    }
}
