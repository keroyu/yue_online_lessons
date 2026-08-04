<?php

namespace App\Jobs;

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
 * Push a reschedule or a cancellation through to Zoom (011 US14 / FR-050).
 *
 * Deliberately downstream of the change it reflects: by the time this runs the
 * slots have moved and the applicant has the mail. Zoom failing here leaves a
 * meeting at the wrong time or one that should have been deleted — annoying,
 * fixable by hand, and never a reason to undo a booking change the applicant
 * has already been told about.
 */
class SyncZoomMeetingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 300];

    /**
     * The meeting id travels as a value rather than being read off the lead:
     * cancelling clears `zoom_meeting_id` immediately, so by the time the queue
     * picks this up the lead no longer knows what to delete.
     */
    public function __construct(
        public string $action,
        public string $meetingId,
        public ?int $leadId = null,
    ) {}

    public function handle(ZoomMeetingService $zoom, ConsultationSlotService $slots): void
    {
        if (!$zoom->isEnabled() || $this->meetingId === '') {
            return;
        }

        if ($this->action === self::ACTION_DELETE) {
            $zoom->deleteMeeting($this->meetingId);

            return;
        }

        $lead = HighTicketLead::find($this->leadId);
        $slot = $lead?->slots()->first();

        if (!$lead || !$slot) {
            Log::warning('SyncZoomMeetingJob: lead or slot gone, nothing to move', [
                'lead_id' => $this->leadId,
            ]);

            return;
        }

        $zoom->updateMeeting($this->meetingId, $slot->starts_at, $slots->minutesFor($lead->booking_code));
    }

    /**
     * Out of retries. The booking change already happened and the applicant has
     * been told, so the only useful thing left is making sure a human hears that
     * Zoom is out of step (比照 D39).
     */
    public function failed(\Throwable $e): void
    {
        Log::error('SyncZoomMeetingJob: giving up, Zoom is out of sync with the booking', [
            'action'     => $this->action,
            'meeting_id' => $this->meetingId,
            'lead_id'    => $this->leadId,
            'error'      => $e->getMessage(),
        ]);

        app(HighTicketBookingService::class)->notifyZoomSyncFailure($this->action, $this->meetingId, $this->leadId);
    }
}
