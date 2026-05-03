<?php

namespace App\Jobs;

use App\Models\Course;
use App\Models\HighTicketLead;
use App\Models\User;
use App\Services\DripService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SubscribeDripLeadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public int $leadId,
        public int $dripCourseId,
    ) {}

    public function handle(DripService $dripService): void
    {
        $lead = HighTicketLead::find($this->leadId);

        if (!$lead) {
            Log::warning('SubscribeDripLeadJob: Lead not found', ['lead_id' => $this->leadId]);
            return;
        }

        $user = User::firstOrCreate(
            ['email' => $lead->email],
            ['nickname' => $lead->name]
        );

        $course = Course::find($this->dripCourseId);

        if (!$course) {
            Log::error('SubscribeDripLeadJob: Drip course not found', ['course_id' => $this->dripCourseId]);
            return;
        }

        $result = $dripService->subscribe($user, $course);

        if ($result['success']) {
            $lead->update(['status' => 'closed']);
        } else {
            Log::info('SubscribeDripLeadJob: Subscription skipped', [
                'lead_id' => $this->leadId,
                'reason' => $result['error'] ?? 'unknown',
            ]);
        }
    }
}
