<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetching the VTT file Zoom produced for a cloud recording (011 US23).
 *
 * Download only — parsing lives in ConsultationTranscriptService, because that
 * half has nothing to do with Zoom and everything to do with what we keep.
 */
class ZoomTranscriptService
{
    /** Transcripts are far bigger than the meeting-CRUD payloads, so this is roomier than ZoomMeetingService's 8s. */
    private const TIMEOUT_SECONDS = 30;
    private const RETRY_TIMES = 2;
    private const RETRY_SLEEP_MS = 500;

    /**
     * Pick the transcript out of a `recording_files` array.
     *
     * `file_type` is the documented discriminator; the extension check is there
     * because a recording can carry more than one text artefact and only the
     * VTT is the timed transcript.
     *
     * @param  array<int, array<string, mixed>>  $files
     * @return array<string, mixed>|null
     */
    public function findTranscriptFile(array $files): ?array
    {
        foreach ($files as $file) {
            $type = strtoupper((string) ($file['file_type'] ?? ''));
            $extension = strtoupper((string) ($file['file_extension'] ?? ''));

            if ($type === 'TRANSCRIPT' || $extension === 'VTT') {
                return $file;
            }
        }

        return null;
    }

    /**
     * Ask Zoom what this meeting currently has (011 US25 / FR-133).
     *
     * The webhook hands us a frozen snapshot of one event; this asks for the
     * present state instead, which is the only way to answer "is the transcript
     * ready yet" on demand. Shaped exactly like the webhook payload so the job
     * downstream keeps one code path — the manual command has done it this way
     * since it was written, and this method is that code, lifted out so the
     * admin button does not become a second copy of it.
     *
     * Returns null when Zoom has no recording for the meeting (404 / 3301 is
     * routine: recordings expire, and not every consultation is recorded).
     *
     * @return array{object: array<string, mixed>, download_token: string}|null
     */
    public function recordingPayload(string $meetingId): ?array
    {
        $response = Http::withToken(app(ZoomMeetingService::class)->token())
            ->acceptJson()
            ->timeout(self::TIMEOUT_SECONDS)
            ->get('https://api.zoom.us/v2/meetings/' . urlencode($meetingId) . '/recordings');

        if (!$response->successful()) {
            Log::info('Zoom: no recording available for this meeting', [
                'meeting_id' => $meetingId,
                'status'     => $response->status(),
            ]);

            return null;
        }

        // No download token outside the webhook path; `download()` falls back to
        // the account token.
        return ['object' => (array) $response->json(), 'download_token' => ''];
    }

    /**
     * Download the file.
     *
     * The webhook's `download_token` is scoped to this recording and is what
     * Zoom expects; the account token is a fallback for the manual command,
     * where no webhook payload exists.
     *
     * @throws \RuntimeException when the download fails
     */
    public function download(string $url, ?string $downloadToken = null): string
    {
        $token = trim((string) $downloadToken);

        if ($token === '') {
            $token = app(ZoomMeetingService::class)->token();
        }

        $response = Http::withToken($token)
            ->timeout(self::TIMEOUT_SECONDS)
            ->retry(self::RETRY_TIMES, self::RETRY_SLEEP_MS, throw: false)
            ->get($url);

        if (!$response->successful()) {
            throw new \RuntimeException(
                'Zoom transcript download failed: ' . $response->status() . ' ' . $response->body()
            );
        }

        return $response->body();
    }
}
