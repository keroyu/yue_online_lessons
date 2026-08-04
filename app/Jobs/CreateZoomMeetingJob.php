<?php

namespace App\Jobs;

use App\Models\Course;
use App\Models\HighTicketLead;
use App\Services\ConsultationSlotService;
use App\Services\HighTicketBookingService;
use App\Services\ZoomMeetingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Create the Zoom meeting for a confirmed booking, then send the confirmation
 * mail with the link in it (011 US12 / D38).
 *
 * The mail is sent from here rather than at confirmation time so the applicant
 * receives one complete email instead of a booking notice followed by a
 * "here's the link" afterthought.
 */
class CreateZoomMeetingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 300];

    public function __construct(public int $leadId) {}

    public function handle(
        ZoomMeetingService $zoom,
        HighTicketBookingService $booking,
        ConsultationSlotService $slots
    ): void {
        $lead = HighTicketLead::find($this->leadId);

        if (!$lead) {
            Log::warning('CreateZoomMeetingJob: lead gone', ['lead_id' => $this->leadId]);

            return;
        }

        $course = Course::find($lead->course_id);
        $slot = $lead->slots()->first();

        if (!$course || !$slot) {
            Log::warning('CreateZoomMeetingJob: course or slot gone', ['lead_id' => $this->leadId]);

            return;
        }

        $minutes = $slots->minutesFor($lead->booking_code);

        $meeting = $zoom->createMeeting(
            $slot->starts_at,
            $minutes,
            "{$course->name} 1v1 諮詢 - {$lead->name}"
        );

        $lead->update([
            'zoom_meeting_id' => $meeting['meeting_id'],
            'zoom_join_url'   => $meeting['join_url'],
        ]);

        $booking->sendConfirmationMail($lead, $course, $meeting['join_url']);
    }

    /**
     * Out of retries. The booking is a settled fact — the applicant confirmed
     * and the slot is theirs — so the confirmation still goes out, just without
     * a link, and the internal recipients are told to open one by hand (D39).
     */
    public function failed(\Throwable $e): void
    {
        Log::error('CreateZoomMeetingJob: giving up, sending confirmation without a link', [
            'lead_id' => $this->leadId,
            'error'   => $e->getMessage(),
        ]);

        $lead = HighTicketLead::find($this->leadId);
        $course = $lead ? Course::find($lead->course_id) : null;

        if (!$lead || !$course) {
            return;
        }

        app(HighTicketBookingService::class)->sendConfirmationMail(
            $lead,
            $course,
            '（會議連結將另行寄出）'
        );
    }
}
