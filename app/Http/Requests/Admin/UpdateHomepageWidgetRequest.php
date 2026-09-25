<?php

namespace App\Http\Requests\Admin;

use App\Models\HomepageWidget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Editing a custom HTML block (002 US23).
 *
 * Same rules as creating one — `area` is here too, because moving a block
 * between the columns is a form field rather than a cross-column drag (D70).
 *
 * The HTML itself is trusted admin input, same licence as the sales-page
 * Markdown and `free_success_md` (002 FR-078) — the only limit is length.
 */
class UpdateHomepageWidgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:100'],
            'html'  => ['required', 'string', 'max:20000'],
            'area'  => ['required', Rule::in(HomepageWidget::AREAS)],
        ];
    }

    public function messages(): array
    {
        return [
            'title.max'     => '標題不可超過 100 字',
            'html.required' => '請填寫 HTML 內容',
            'html.max'      => 'HTML 內容不可超過 20000 字',
            'area.required' => '請選擇要放在左欄或右欄',
            'area.in'       => '欄位位置只能是左欄或右欄',
        ];
    }
}
