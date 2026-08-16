<?php

namespace App\Console\Commands;

use App\Jobs\ProcessZoomTranscriptJob;
use App\Models\ConsultationNote;
use App\Services\ZoomMeetingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Pull one consultation's transcript by hand (011 US23).
 *
 * Deliberately not scheduled — the webhook is the production path. This exists
 * so the pipeline can be exercised end to end without waiting for a real
 * meeting to finish, and to rescue a session whose webhook was missed.
 */
class FetchConsultationTranscript extends Command
{
    protected $signature = 'booking:fetch-transcript {note : consultation_notes 的 id} {--force : 連人工修訂過的內容也一併覆寫}';

    protected $description = '向 Zoom 取回指定面談的逐字稿，校訂後產生摘要';

    public function handle(): int
    {
        $note = ConsultationNote::find((int) $this->argument('note'));

        if ($note === null) {
            $this->error('找不到這筆面談紀錄');

            return self::FAILURE;
        }

        $meetingId = trim((string) $note->zoom_meeting_id);

        if ($meetingId === '') {
            $this->error('這筆面談沒有 Zoom 會議 id，無法取回逐字稿');

            return self::FAILURE;
        }

        $zoom = app(ZoomMeetingService::class);

        if (!$zoom->isEnabled()) {
            $this->error('Zoom 憑證尚未設定（後台 → API 設定）');

            return self::FAILURE;
        }

        $this->info("向 Zoom 查詢會議 {$meetingId} 的錄影檔…");

        $response = Http::withToken($zoom->token())
            ->acceptJson()
            ->timeout(30)
            ->get("https://api.zoom.us/v2/meetings/{$meetingId}/recordings");

        if (!$response->successful()) {
            $this->error('Zoom 回應 ' . $response->status() . '：' . $response->body());
            $this->line('雲端錄影可能已過保存期限。');

            return self::FAILURE;
        }

        // Same shape the webhook delivers, so the job takes one code path.
        ProcessZoomTranscriptJob::dispatchSync(
            $note->id,
            ['object' => $response->json(), 'download_token' => ''],
            (bool) $this->option('force')
        );

        $note->refresh();

        $this->info('逐字稿字元數：' . mb_strlen((string) $note->transcript));
        $this->info('摘要字元數：' . mb_strlen((string) $note->summary));

        return self::SUCCESS;
    }
}
