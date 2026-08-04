<?php

namespace Tests\Feature\HighTicket;

use App\Models\Course;
use App\Models\HighTicketLead;
use App\Services\CalendarInviteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * 011 US14 — the .ics itself (FR-046 / FR-047 / D51).
 *
 * Written against the wire format rather than the service's internals because
 * that is what the calendar client actually parses. The folding cases matter
 * most: a Chinese SUMMARY split mid-character turns the whole property into
 * mojibake, and nothing in the app would notice.
 */
class CalendarInviteTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourse(): Course
    {
        return Course::create([
            'name'                   => '經營者時間銀行 1v1 諮詢',
            'slug'                   => 'ht-' . uniqid(),
            'tagline'                => 'tag',
            'description'            => 'desc',
            'price'                  => 50000,
            'instructor_name'        => 'Tester',
            'type'                   => 'high_ticket',
            'status'                 => 'selling',
            'course_type'            => 'standard',
            'is_published'           => true,
            'is_visible'             => true,
            'payment_gateway'        => 'payuni',
            'high_ticket_hide_price' => true,
        ])->fresh();
    }

    private function makeLead(Course $course, array $overrides = []): HighTicketLead
    {
        return HighTicketLead::create(array_merge([
            'name'      => '王小明',
            'email'     => 'ming@example.com',
            'course_id' => $course->id,
            'status'    => 'pending',
            'booked_at' => now(),
        ], $overrides));
    }

    /**
     * Unfold an .ics back into logical lines so assertions can look at property
     * values instead of the wrapped representation — this is exactly what a
     * calendar client does before parsing.
     */
    private function unfold(string $ics): string
    {
        return str_replace("\r\n ", '', $ics);
    }

    private function property(string $ics, string $name): string
    {
        foreach (explode("\r\n", $this->unfold($ics)) as $line) {
            if (str_starts_with($line, $name . ':') || str_starts_with($line, $name . ';')) {
                return $line;
            }
        }

        return '';
    }

    public function test_invite_carries_the_required_calendar_envelope(): void
    {
        $course = $this->makeCourse();
        $lead = $this->makeLead($course);

        $ics = app(CalendarInviteService::class)->invite(
            $lead,
            $course,
            Carbon::parse('2026-09-01 06:00:00', 'UTC'),
            30,
            'https://us05web.zoom.us/j/123'
        );

        $this->assertStringContainsString('BEGIN:VCALENDAR', $ics);
        $this->assertStringContainsString('VERSION:2.0', $ics);
        $this->assertStringContainsString('METHOD:REQUEST', $ics);
        $this->assertStringContainsString('BEGIN:VEVENT', $ics);
        $this->assertStringContainsString('STATUS:CONFIRMED', $ics);
        $this->assertStringContainsString('END:VCALENDAR', $ics);
    }

    public function test_times_are_utc_and_span_the_consultation_length(): void
    {
        $course = $this->makeCourse();
        $lead = $this->makeLead($course);

        $ics = app(CalendarInviteService::class)->invite(
            $lead,
            $course,
            Carbon::parse('2026-09-01 06:00:00', 'UTC'),
            45,
            null
        );

        $this->assertSame('DTSTART:20260901T060000Z', $this->property($ics, 'DTSTART'));
        $this->assertSame('DTEND:20260901T064500Z', $this->property($ics, 'DTEND'));
    }

    /**
     * A Taipei-local start must still be written as UTC — the slot table stores
     * UTC (D32) but a caller could hand us a localised Carbon.
     */
    public function test_a_taipei_carbon_is_converted_rather_than_written_verbatim(): void
    {
        $course = $this->makeCourse();
        $lead = $this->makeLead($course);

        $ics = app(CalendarInviteService::class)->invite(
            $lead,
            $course,
            Carbon::parse('2026-09-01 14:00:00', 'Asia/Taipei'),
            30,
            null
        );

        $this->assertSame('DTSTART:20260901T060000Z', $this->property($ics, 'DTSTART'));
    }

    public function test_uid_is_derived_from_the_lead_and_stays_stable(): void
    {
        $course = $this->makeCourse();
        $lead = $this->makeLead($course);
        $service = app(CalendarInviteService::class);
        $at = Carbon::parse('2026-09-01 06:00:00', 'UTC');

        $first = $this->property($service->invite($lead, $course, $at, 30, null), 'UID');
        $second = $this->property($service->invite($lead->fresh(), $course, $at->copy()->addDay(), 45, null), 'UID');

        $this->assertStringContainsString("high-ticket-lead-{$lead->id}@", $first);
        $this->assertSame($first, $second, 'UID must not change when the booking moves');
    }

    public function test_sequence_comes_from_the_lead(): void
    {
        $course = $this->makeCourse();
        $lead = $this->makeLead($course, ['calendar_sequence' => 3]);

        $ics = app(CalendarInviteService::class)->invite(
            $lead,
            $course,
            Carbon::parse('2026-09-01 06:00:00', 'UTC'),
            30,
            null
        );

        $this->assertSame('SEQUENCE:3', $this->property($ics, 'SEQUENCE'));
    }

    public function test_every_line_ends_with_crlf(): void
    {
        $course = $this->makeCourse();
        $lead = $this->makeLead($course);

        $ics = app(CalendarInviteService::class)->invite(
            $lead,
            $course,
            Carbon::parse('2026-09-01 06:00:00', 'UTC'),
            30,
            'https://us05web.zoom.us/j/123'
        );

        // No bare LF anywhere: strip every CRLF and nothing should be left.
        $this->assertStringNotContainsString("\n", str_replace("\r\n", '', $ics));
    }

    public function test_no_line_exceeds_75_octets(): void
    {
        $course = $this->makeCourse();
        $lead = $this->makeLead($course, ['name' => str_repeat('王小明', 40)]);

        $ics = app(CalendarInviteService::class)->invite(
            $lead,
            $course,
            Carbon::parse('2026-09-01 06:00:00', 'UTC'),
            30,
            'https://us05web.zoom.us/j/1234567890?pwd=' . str_repeat('a', 120)
        );

        foreach (explode("\r\n", $ics) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line), "行超過 75 octets：{$line}");
        }
    }

    /**
     * The one that actually bites: folding by bytes without respecting UTF-8
     * boundaries produces a file that looks fine in a hex dump and renders as
     * garbage in the calendar.
     */
    public function test_folded_chinese_survives_a_round_trip(): void
    {
        $course = $this->makeCourse();
        $longName = str_repeat('王小明', 40);
        $lead = $this->makeLead($course, ['name' => $longName]);

        $ics = app(CalendarInviteService::class)->invite(
            $lead,
            $course,
            Carbon::parse('2026-09-01 06:00:00', 'UTC'),
            30,
            null
        );

        $unfolded = $this->unfold($ics);

        $this->assertTrue(mb_check_encoding($unfolded, 'UTF-8'), '展開後不是合法的 UTF-8，folding 切在多位元組字元中間');
        $this->assertStringContainsString($longName, $unfolded);
        $this->assertStringContainsString($course->name, $unfolded);
    }

    public function test_special_characters_are_escaped(): void
    {
        $course = $this->makeCourse();
        $lead = $this->makeLead($course, ['name' => 'A;B,C\\D']);

        $ics = app(CalendarInviteService::class)->invite(
            $lead,
            $course,
            Carbon::parse('2026-09-01 06:00:00', 'UTC'),
            30,
            null
        );

        $summary = $this->property($ics, 'SUMMARY');

        $this->assertStringContainsString('A\\;B\\,C\\\\D', $summary);
    }

    public function test_zoom_link_lands_in_location_and_description(): void
    {
        $course = $this->makeCourse();
        $lead = $this->makeLead($course);

        $ics = app(CalendarInviteService::class)->invite(
            $lead,
            $course,
            Carbon::parse('2026-09-01 06:00:00', 'UTC'),
            30,
            'https://us05web.zoom.us/j/123'
        );

        $this->assertStringContainsString('https://us05web.zoom.us/j/123', $this->property($ics, 'LOCATION'));
        $this->assertStringContainsString('us05web.zoom.us/j/123', $this->unfold($ics));
    }

    /** Zoom being off must not cost the applicant the calendar entry (FR-046). */
    public function test_invite_is_still_produced_without_a_zoom_link(): void
    {
        $course = $this->makeCourse();
        $lead = $this->makeLead($course);

        $ics = app(CalendarInviteService::class)->invite(
            $lead,
            $course,
            Carbon::parse('2026-09-01 06:00:00', 'UTC'),
            30,
            null
        );

        $this->assertStringContainsString('BEGIN:VEVENT', $ics);
        $this->assertSame('DTSTART:20260901T060000Z', $this->property($ics, 'DTSTART'));
        $this->assertStringNotContainsString('LOCATION:', $ics);
    }

    public function test_attendee_and_organizer_are_present(): void
    {
        $course = $this->makeCourse();
        $lead = $this->makeLead($course);

        $ics = app(CalendarInviteService::class)->invite(
            $lead,
            $course,
            Carbon::parse('2026-09-01 06:00:00', 'UTC'),
            30,
            null
        );

        $this->assertStringContainsString('mailto:ming@example.com', $this->property($ics, 'ATTENDEE'));
        $this->assertNotSame('', $this->property($ics, 'ORGANIZER'));
    }

    public function test_cancellation_uses_the_cancel_method_and_same_uid(): void
    {
        $course = $this->makeCourse();
        $lead = $this->makeLead($course, ['calendar_sequence' => 2]);
        $service = app(CalendarInviteService::class);
        $at = Carbon::parse('2026-09-01 06:00:00', 'UTC');

        $invite = $service->invite($lead, $course, $at, 30, null);
        $cancel = $service->cancellation($lead, $course, $at, 30);

        $this->assertStringContainsString('METHOD:CANCEL', $cancel);
        $this->assertStringContainsString('STATUS:CANCELLED', $cancel);
        $this->assertSame($this->property($invite, 'UID'), $this->property($cancel, 'UID'));
        // Without an ATTENDEE line Outlook ignores the cancellation entirely.
        $this->assertStringContainsString('mailto:ming@example.com', $this->property($cancel, 'ATTENDEE'));
    }
}
