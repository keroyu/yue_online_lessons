<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsultationNote;
use App\Services\ConsultationTranscriptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Editing one consultation record (011 US23).
 *
 * Inline validation and JSON responses, matching HighTicketLeadController's
 * existing shape — authorisation is the `staff` route group's job.
 */
class ConsultationNoteController extends Controller
{
    public function updateSummary(Request $request, ConsultationNote $note): JsonResponse
    {
        $validated = $request->validate([
            'summary' => ['nullable', 'string', 'max:20000'],
        ]);

        $note->update([
            'summary'           => $validated['summary'] ?? null,
            'summary_edited_at' => now(),
        ]);

        return response()->json([
            'success'           => true,
            'summary_edited_at' => $note->summary_edited_at?->toIso8601String(),
        ]);
    }

    public function updateTranscript(Request $request, ConsultationNote $note): JsonResponse
    {
        $validated = $request->validate([
            'transcript' => ['nullable', 'string', 'max:200000'],
        ]);

        $note->update([
            'transcript'           => $validated['transcript'] ?? null,
            'transcript_edited_at' => now(),
        ]);

        return response()->json([
            'success'              => true,
            'transcript_edited_at' => $note->transcript_edited_at?->toIso8601String(),
        ]);
    }

    /**
     * Re-run the summary from the stored transcript (FR-111).
     *
     * Never touches Zoom: cloud recordings expire, and keeping the proofread
     * copy is precisely what makes the summary format changeable afterwards.
     */
    public function regenerateSummary(ConsultationNote $note, ConsultationTranscriptService $transcripts): JsonResponse
    {
        if (trim((string) $note->transcript) === '') {
            return response()->json([
                'message' => '這場面談還沒有逐字稿，無法產生摘要',
            ], 422);
        }

        $summary = $transcripts->summarise($note->transcript);

        if ($summary === null) {
            return response()->json([
                'message' => 'AI 尚未設定或沒有回傳內容，請確認 AI 設定頁的 API Key',
            ], 422);
        }

        $note->update([
            'summary'              => $summary,
            'summary_generated_at' => now(),
            // An explicit "regenerate" is the admin releasing their own lock.
            'summary_edited_at'    => null,
        ]);

        return response()->json([
            'success'              => true,
            'summary'              => $summary,
            'summary_generated_at' => $note->summary_generated_at?->toIso8601String(),
        ]);
    }
}
