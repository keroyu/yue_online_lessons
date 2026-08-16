<?php

namespace App\Jobs;

use App\Models\ConsultationNote;
use App\Services\ConsultationTranscriptService;
use App\Services\ZoomTranscriptService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Fetch, proofread and summarise one consultation's transcript (011 US23).
 *
 * The backoff is generous because Zoom produces the audio transcript a few
 * minutes after the recording itself — an early `recording.completed` legitimately
 * arrives before the file exists, and waiting is the correct response.
 */
class ProcessZoomTranscriptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    /**
     * Minutes, not seconds — unlike every other job here.
     *
     * Proofreading runs one sequential LLM call per ~4000-character chunk, so an
     * hour-long consultation is comfortably several minutes of work. The default
     * 60-second worker timeout would kill this mid-transcript, and the default
     * 90-second `retry_after` would hand the same job to a second worker while
     * the first was still running. Hence both this and the dedicated connection.
     */
    public int $timeout = 1500;

    /**
     * @param  array<string, mixed>  $payload  Zoom's `payload` object plus `download_token`
     */
    public function __construct(
        public int $noteId,
        public array $payload = [],
        public bool $force = false,
    ) {
        // Only when we are genuinely queueing. Under `sync` (tests, and the
        // manual artisan command) the work runs inline and there is no lease to
        // outlive, so forcing the connection there would just stop it running.
        if (config('queue.default') !== 'sync') {
            $this->onConnection('database_long');
        }
    }

    public function handle(
        ZoomTranscriptService $zoom,
        ConsultationTranscriptService $transcripts,
    ): void {
        $note = ConsultationNote::find($this->noteId);

        if ($note === null) {
            return;
        }

        $this->fetchTranscript($note, $zoom, $transcripts);
        $this->writeSummary($note, $transcripts);
    }

    private function fetchTranscript(
        ConsultationNote $note,
        ZoomTranscriptService $zoom,
        ConsultationTranscriptService $transcripts,
    ): void {
        // A human edit outranks anything the model would produce, and skipping
        // early also means we never pay for tokens to overwrite it (FR-110).
        if ($note->transcriptIsLocked() && !$this->force) {
            Log::info('Consultation transcript: skipped, human-edited', ['note_id' => $note->id]);

            return;
        }

        $files = (array) data_get($this->payload, 'object.recording_files', []);

        if ($files === []) {
            return;
        }

        $file = $zoom->findTranscriptFile($files);

        if ($file === null) {
            // Not an error — the transcript trails the recording. Throwing hands
            // it to the backoff instead of silently giving up.
            throw new \RuntimeException('Zoom recording has no transcript file yet');
        }

        $vtt = $zoom->download(
            (string) ($file['download_url'] ?? ''),
            (string) ($this->payload['download_token'] ?? '')
        );

        $dialogue = $transcripts->vttToDialogue($vtt);
        $dialogue = $transcripts->normaliseSpeakers($dialogue, $note);
        $transcript = $transcripts->proofread($dialogue, $note);

        $note->update([
            'transcript'            => $transcript,
            'transcript_fetched_at' => now(),
        ]);

        Log::info('Consultation transcript stored', [
            'note_id' => $note->id,
            'chars'   => mb_strlen($transcript),
        ]);
    }

    private function writeSummary(ConsultationNote $note, ConsultationTranscriptService $transcripts): void
    {
        $note->refresh();

        if ($note->summaryIsLocked() && !$this->force) {
            Log::info('Consultation summary: skipped, human-edited', ['note_id' => $note->id]);

            return;
        }

        $summary = $transcripts->summarise((string) $note->transcript);

        if ($summary === null) {
            return;
        }

        $note->update([
            'summary'              => $summary,
            'summary_generated_at' => now(),
            'summary_edited_at'    => null,
        ]);
    }
}
