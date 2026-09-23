<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Whole-document save for a course roadmap (004 US7 / FR-020).
 *
 * Ids are optional on purpose: an item carrying one is an update, an item
 * without one is an insert. Ownership of every incoming id is checked in
 * CourseRoadmapService::sync(), which has the course in hand (FR-021).
 */
class CourseRoadmapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'roadmap_title' => ['nullable', 'string', 'max:100'],

            'stages'                      => ['present', 'array'],
            'stages.*.id'                 => ['nullable', 'integer'],
            'stages.*.title'              => ['required', 'string', 'max:200'],
            'stages.*.description_md'     => ['nullable', 'string', 'max:5000'],

            'stages.*.checkpoints'        => ['present', 'array'],
            'stages.*.checkpoints.*.id'    => ['nullable', 'integer'],
            'stages.*.checkpoints.*.label' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'roadmap_title.max'                  => 'Roadmap 標題不可超過 100 字',
            'stages.*.title.required'            => '階段標題為必填',
            'stages.*.title.max'                 => '階段標題不可超過 200 字',
            'stages.*.description_md.max'        => '階段說明不可超過 5000 字',
            'stages.*.checkpoints.*.label.required' => '檢核項目內容為必填',
            'stages.*.checkpoints.*.label.max'   => '檢核項目不可超過 500 字',
        ];
    }

    public function attributes(): array
    {
        return [
            'roadmap_title' => 'Roadmap 標題',
            'stages'        => '階段',
        ];
    }
}
