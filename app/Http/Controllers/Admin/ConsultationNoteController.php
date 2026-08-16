<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsultationNote;
use App\Services\ConsultationTranscriptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

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

    /**
     * Hand the transcript over as a file (FR-120).
     *
     * The panel stopped rendering it on screen, so this is now the only way to
     * read one. Serving it from here rather than building the file in the
     * browser is what lets the leads payload drop the body entirely — a page of
     * twenty leads was otherwise shipping every transcript those customers ever
     * had, whether or not anyone opened them.
     */
    public function downloadTranscript(ConsultationNote $note): Response
    {
        $transcript = (string) $note->transcript;

        if (trim($transcript) === '') {
            abort(404, '這場面談還沒有逐字稿');
        }

        $met = $note->met_at?->timezone(config('app.timezone'));

        $header = implode("\n", array_filter([
            '面談時間：' . ($met?->format('Y-m-d H:i') ?? '未定'),
            $note->course?->name ? '課程：' . $note->course->name : null,
            $note->consultant?->nickname ? '顧問：' . $note->consultant->nickname : null,
            '客戶：' . $note->email,
            '講者已匿名化為「顧問」與「客戶」。',
            str_repeat('─', 20),
            '',
            '',
        ]));

        // A BOM so Windows Notepad reads the Chinese as UTF-8 instead of mojibake.
        $body = "\xEF\xBB\xBF" . $header . $transcript . "\n";

        $filename = sprintf('consultation-%s-%d.txt', $met?->format('Ymd') ?? 'undated', $note->id);

        return response($body, 200, [
            'Content-Type'        => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Drop one session outright (FR-118) — a mis-booking, a test, a no-show.
     *
     * A hard delete, not a soft one: there is no restore UI to justify keeping
     * the row, and a hidden row would still have to be filtered out of the
     * email-keyed history everywhere it is read.
     *
     * Deliberately unrestricted, including for notes that already hold content.
     * The case that prompted this is precisely a short accidental meeting that
     * *did* produce a recording, so refusing to delete anything non-empty would
     * block the very thing being asked for. The escalated confirmation lives in
     * the UI, and the audit line below is what makes an unwanted deletion
     * traceable afterwards.
     */
    public function destroy(Request $request, ConsultationNote $note): JsonResponse
    {
        // Counts, never content — FR-109 keeps transcript text out of the logs.
        Log::warning('Consultation record deleted', [
            'note_id'         => $note->id,
            'lead_id'         => $note->lead_id,
            'met_at'          => $note->met_at?->toIso8601String(),
            'transcript_chars' => mb_strlen((string) $note->transcript),
            'summary_chars'   => mb_strlen((string) $note->summary),
            'deleted_by'      => $request->user()?->id,
        ]);

        $note->delete();

        return response()->json(['success' => true]);
    }
}
