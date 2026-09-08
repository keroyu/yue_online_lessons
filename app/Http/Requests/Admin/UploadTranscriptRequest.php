<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 011 US34 / FR-182 — the file an admin uploads to replace a wrong transcript.
 *
 * The extension list is a courtesy filter, not the decision: what actually
 * matters is whether the contents parse into dialogue, and the controller
 * checks that. A VTT saved as .txt is a normal thing for a person to hand over.
 */
class UploadTranscriptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by the staff middleware
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Three hours of transcript is roughly 200KB, so 2MB is ten times
            // the room anything real needs.
            'file' => ['required', 'file', 'max:2048', 'mimes:vtt,srt,txt,md'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => '請選擇要上傳的逐字稿檔案',
            'file.file'     => '上傳的內容不是檔案',
            'file.max'      => '檔案不得超過 2MB',
            'file.mimes'    => '只接受 .vtt / .srt / .txt / .md 檔',
        ];
    }
}
