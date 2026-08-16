<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessZoomTranscriptJob;
use App\Models\ConsultationNote;
use App\Services\ZoomWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Zoom cloud-recording webhook (011 US23 / FR-101–FR-105).
 *
 * Zoom wants a reply inside three seconds, so this does the least possible:
 * verify, find the record, queue the work, acknowledge.
 */
class ZoomController extends Controller
{
    /** Both are accepted (D88): the transcript trails the recording, and only one of these may be subscribable. */
    private const HANDLED_EVENTS = [
        'recording.transcript_completed',
        'recording.completed',
    ];

    public function handle(Request $request, ZoomWebhookService $webhooks): JsonResponse
    {
        $event = (string) $request->input('event', '');

        // Answered before verification on purpose: this handshake is what
        // establishes the shared secret's use in the first place.
        if ($event === 'endpoint.url_validation') {
            // Without the secret we still answer, but with a hash Zoom cannot
            // match — and Zoom's only feedback is "validation failed", which
            // says nothing about why. The log line is the difference between a
            // two-minute fix and an afternoon.
            if (!$webhooks->isEnabled()) {
                Log::warning('Zoom webhook: URL validation attempted before the Secret Token was saved — set it in 後台 → API 設定 → Zoom 會議 first');
            }

            $plainToken = (string) $request->input('payload.plainToken', '');

            return response()->json($webhooks->challengeResponse($plainToken));
        }

        if (!$webhooks->verify($request)) {
            Log::warning('Zoom webhook: signature verification failed', ['event' => $event]);

            return response()->json(['message' => 'invalid signature'], 401);
        }

        if (!in_array($event, self::HANDLED_EVENTS, true)) {
            return response()->json(['message' => 'ignored']);
        }

        // Everything past this point is best-effort. A non-2xx only makes Zoom
        // resend, and a resend does not fix a payload we cannot parse
        // (沿用 Portaly webhook 的既有立場).
        try {
            $meetingId = trim((string) $request->input('payload.object.id', ''));

            $note = $meetingId !== ''
                ? ConsultationNote::where('zoom_meeting_id', $meetingId)->first()
                : null;

            if ($note === null) {
                Log::info('Zoom webhook: no consultation record for this meeting', ['meeting_id' => $meetingId]);

                return response()->json(['message' => 'no matching record']);
            }

            ProcessZoomTranscriptJob::dispatch(
                $note->id,
                array_merge(
                    (array) $request->input('payload', []),
                    ['download_token' => (string) $request->input('download_token', '')]
                )
            );
        } catch (\Exception $e) {
            Log::error('Zoom webhook: processing failed', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['message' => 'ok']);
    }
}
