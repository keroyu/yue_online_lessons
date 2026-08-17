<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessZoomTranscriptJob;
use App\Models\ConsultationNote;
use App\Services\ConsultationTranscriptService;
use App\Services\ZoomMeetingService;
use App\Services\ZoomTranscriptService;
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
     * Go and ask Zoom for this session's transcript now (US25 / FR-133).
     *
     * Two-stage on purpose. Asking Zoom what it has takes a second, and that
     * second separates the three answers an admin presses this button for: no
     * recording at all, transcript not ready yet, transcript ready. Only the
     * third is slow work (download, proofread, summarise — minutes on a long
     * consultation), and only it goes to the queue. Queueing the whole thing
     * would turn the two common answers into "press, wait three minutes, see
     * nothing changed"; running the whole thing inline would outlive PHP-FPM's
     * read timeout while the work carried on invisibly (FR-117).
     *
     * No "already has a transcript" guard here by design (FR-134): the job's
     * own `transcriptIsSettled()` covers it, and the button is not rendered for
     * those sessions.
     */
    public function fetchTranscript(
        ConsultationNote $note,
        ZoomMeetingService $zoom,
        ZoomTranscriptService $transcripts,
    ): JsonResponse {
        if (!$zoom->isEnabled()) {
            return response()->json([
                'message' => 'Zoom 憑證尚未設定，請先到後台 → API 設定填入 Zoom 帳號資訊',
            ], 422);
        }

        $meetingId = trim((string) $note->zoom_meeting_id);

        if ($meetingId === '') {
            return response()->json([
                'message' => '這場面談沒有對應的 Zoom 會議，無法抓取逐字稿',
            ], 422);
        }

        $payload = $transcripts->recordingPayload($meetingId);

        if ($payload === null) {
            return response()->json([
                'message' => 'Zoom 上找不到這場會議的錄影 —— 可能沒有開啟雲端錄影，或錄影已過保存期限',
            ], 422);
        }

        $files = (array) data_get($payload, 'object.recording_files', []);

        if ($transcripts->findTranscriptFile($files) === null) {
            return response()->json([
                'message' => 'Zoom 的逐字稿還沒產出（錄影結束後通常還要 1–4 小時），請稍後再試一次',
            ], 422);
        }

        ProcessZoomTranscriptJob::dispatch($note->id, $payload);

        return response()->json([
            'queued'  => true,
            'message' => '已排入處理，約 1–3 分鐘後重新整理即可看到逐字稿與摘要',
        ], 202);
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
