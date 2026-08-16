<?php

namespace App\Console\Commands;

use App\Models\ConsultationNote;
use App\Models\HighTicketLead;
use App\Services\HighTicketBookingService;
use Illuminate\Console\Command;

/**
 * Create the consultation records US23 never got to make (011 US23 / FR-119).
 *
 * `recordConsultationNote()` runs at booking confirmation, so every booking
 * confirmed before this feature shipped has no record at all — and without one
 * the Zoom webhook has nothing to match on and drops the transcript. Those
 * meetings are usually still in the future, so backfilling is the difference
 * between getting their transcripts and losing them.
 *
 * Safe to run repeatedly: the underlying match is on `zoom_meeting_id`, so an
 * existing record is moved, never duplicated.
 */
class BackfillConsultationNotes extends Command
{
    protected $signature = 'booking:backfill-consultation-notes {--dry-run : 只列出將建立的紀錄，不寫入}';

    protected $description = '為功能上線前已確認的預約補建面談紀錄';

    public function handle(HighTicketBookingService $bookings): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $leads = HighTicketLead::with(['course', 'slots'])
            ->whereNotNull('confirmed_at')
            ->whereNull('cancelled_at')
            ->orderBy('id')
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($leads as $lead) {
            $slot = $lead->slots->first();
            $course = $lead->course;

            // Same two preconditions the confirmation path has: no slot means no
            // meeting to record, and the record's course column is not nullable.
            if (!$slot || !$course) {
                $skipped++;
                continue;
            }

            if ($this->alreadyRecorded($lead)) {
                $skipped++;
                continue;
            }

            $this->line(sprintf(
                '  lead %-5d %-38s %s  zoom=%s',
                $lead->id,
                $lead->email,
                $slot->starts_at,
                $lead->zoom_meeting_id ?: '（無）'
            ));

            if (!$dryRun) {
                $bookings->recordConsultationNote($lead, $course, $slot->starts_at);
            }

            $created++;
        }

        $this->newLine();
        $this->info($dryRun
            ? "試跑：會補建 {$created} 筆，略過 {$skipped} 筆（已有紀錄或無時段）"
            : "已補建 {$created} 筆，略過 {$skipped} 筆（已有紀錄或無時段）");

        if ($dryRun && $created > 0) {
            $this->comment('確認無誤後拿掉 --dry-run 再跑一次。');
        }

        return self::SUCCESS;
    }

    private function alreadyRecorded(HighTicketLead $lead): bool
    {
        $meetingId = trim((string) ($lead->zoom_meeting_id ?? ''));

        return $meetingId !== ''
            ? ConsultationNote::where('zoom_meeting_id', $meetingId)->exists()
            : ConsultationNote::where('lead_id', $lead->id)->exists();
    }
}
