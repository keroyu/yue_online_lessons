<?php

namespace Tests\Support;

use App\Models\ConsultationSlot;
use App\Models\Course;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use App\Services\ConsultationSlotService;
use App\Services\HighTicketBookingService;
use Illuminate\Support\Carbon;

/**
 * Shared setup for the two-stage booking flow (011 US9–US11).
 *
 * Since a booking now needs a questionnaire, a slot and a verify template,
 * every test that just wants "somebody booked this course" would otherwise
 * repeat twenty lines of arrangement.
 */
trait BooksHighTicket
{
    protected function seedVerifyTemplate(): void
    {
        if (EmailTemplate::forEvent('high_ticket_booking_verify')->exists()) {
            return;
        }

        EmailTemplate::create([
            'name'       => '預約待確認',
            'event_type' => 'high_ticket_booking_verify',
            'subject'    => '請確認你的 {{course_name}} 預約',
            'body_md'    => 'Hi {{user_name}}，請點 {{confirm_url}}',
        ]);
    }

    /** Consecutive 15-minute units starting tomorrow at 10:00 Taipei time. */
    protected function seedSlots(int $count = 16): Carbon
    {
        $at = Carbon::parse('+1 day 10:00', ConsultationSlotService::DISPLAY_TZ)->utc();

        for ($i = 0; $i < $count; $i++) {
            ConsultationSlot::firstOrCreate([
                'starts_at' => $at->copy()->addMinutes($i * ConsultationSlot::UNIT_MINUTES),
            ]);
        }

        return $at;
    }

    /**
     * The next start that is actually free, so consecutive calls in one test
     * book different times instead of fighting over 10:00.
     */
    protected function nextFreeStart(int $minutes = ConsultationSlotService::DEFAULT_MINUTES): Carbon
    {
        $this->seedSlots();

        $starts = app(ConsultationSlotService::class)->availableStarts($minutes);

        if ($starts === []) {
            throw new \RuntimeException('測試時段用完了，請把 seedSlots() 的數量調高');
        }

        return $starts[0];
    }

    /**
     * A complete application payload. Only the fields a test cares about need
     * overriding.
     */
    protected function applicationData(array $overrides = []): array
    {
        return array_merge([
            'name'           => 'Booker',
            'email'          => 'booker@example.com',
            'phone'          => '0912345678',
            'occupation'     => '設計師，6 年',
            'bottleneck'     => '收入不穩',
            'expertise'      => '品牌設計',
            'social_url'     => null,
            'commitments'    => [true, true, true, true, true],
            'slot_starts_at' => $this->nextFreeStart()->toIso8601String(),
        ], $overrides);
    }

    /** Submit an application (stage one). */
    protected function applyForBooking(Course $course, array $overrides = []): array
    {
        $this->seedVerifyTemplate();

        return app(HighTicketBookingService::class)->apply($course, $this->applicationData($overrides));
    }

    /** Submit and then click the emailed link — a complete booking. */
    protected function applyAndConfirm(Course $course, array $overrides = []): array
    {
        $this->applyForBooking($course, $overrides);

        $email = $overrides['email'] ?? 'booker@example.com';
        $lead = HighTicketLead::where('email', $email)->latest('id')->firstOrFail();

        return app(HighTicketBookingService::class)->confirm($lead->confirm_token);
    }
}
