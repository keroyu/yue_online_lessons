<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by admin middleware
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:200', 'unique:posts,slug', 'regex:/^[a-z0-9\-]+$/'],
            'body_md' => ['required', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'cover_image' => ['nullable', 'image', 'max:10240'],
            'og_image' => ['nullable', 'image', 'max:10240'],
            'status' => ['required', 'in:draft,scheduled,published'],
            'published_at' => ['nullable', 'date', 'required_if:status,scheduled'],
            'is_featured' => ['nullable', 'boolean'],
            'related_course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'related_post_ids' => ['nullable', 'array'],
            'related_post_ids.*' => ['integer', 'exists:posts,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => '請輸入文章標題',
            'slug.required' => '請輸入網址代稱（slug）',
            'slug.unique' => '此網址代稱已被使用',
            'slug.regex' => '網址代稱只能用小寫英文、數字與連字號',
            'body_md.required' => '請輸入文章內容',
            'status.in' => '文章狀態無效',
            'published_at.required_if' => '排程發佈需填發佈時間',
            'related_course_id.exists' => '選擇的引流課程不存在',
        ];
    }
}
