<?php

namespace Tests\Feature\HighTicket;

use App\Models\ConsultationNote;
use App\Models\Course;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use App\Services\HighTicketBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\BooksHighTicket;
use Tests\TestCase;

/**
 * 011 US23 — the consultation record's lifecycle (D92 / FR-113–FR-116).
 *
 * The point of this table is that it survives the lead: one customer, many
 * sessions, keyed on email. These tests hold that shape in place.
 */
class ConsultationNoteTest extends TestCase
{
    use BooksHighTicket, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->seedTemplates();
    }

    private function seedTemplates(): void
    {
        foreach ([
            ['event_type' => 'high_ticket_booking_confirmation', 'name' => '預約確認', 'subject' => '確認', 'body_md' => '{{slot_time}}'],
            ['event_type' => 'high_ticket_booking_rescheduled', 'name' => '改期', 'subject' => '改期', 'body_md' => '{{slot_time}}'],
            ['event_type' => 'high_ticket_booking_cancelled', 'name' => '取消', 'subject' => '取消', 'body_md' => '{{slot_time}}'],
        ] as $template) {
            EmailTemplate::updateOrCreate(['event_type' => $template['event_type']], $template);
        }
    }

    private function makeHighTicketCourse(): Course
    {
        return Course::create([
            'name'                   => 'HT Course',
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

    // ── 建立 ──

    public function test_confirming_a_booking_creates_one_consultation_note(): void
    {
        $course = $this->makeHighTicketCourse();

        $this->applyAndConfirm($course);

        $note = ConsultationNote::first();

        $this->assertNotNull($note);
        $this->assertSame('booker@example.com', $note->email);
        $this->assertSame(ConsultationNote::SOURCE_HIGH_TICKET, $note->source);
        $this->assertSame($course->id, $note->course_id);
        $this->assertNotNull($note->met_at);
        $this->assertNull($note->transcript);
        $this->assertNull($note->summary);
    }

    public function test_note_email_is_stored_lowercase(): void
    {
        $course = $this->makeHighTicketCourse();

        $this->applyAndConfirm($course, ['email' => 'MixedCase@Example.COM']);

        $this->assertSame('mixedcase@example.com', ConsultationNote::first()->email);
    }

    // ── 改期與取消 ──

    public function test_rescheduling_moves_met_at_without_creating_a_second_row(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->applyAndConfirm($course);

        $lead = HighTicketLead::first();
        $original = ConsultationNote::first()->met_at;

        $newStart = $this->nextFreeStart();
        app(HighTicketBookingService::class)->reschedule($lead, $newStart);

        $this->assertSame(1, ConsultationNote::count());
        $moved = ConsultationNote::first()->met_at;
        $this->assertNotEquals($original->toIso8601String(), $moved->toIso8601String());
        $this->assertSame($newStart->toIso8601String(), $moved->toIso8601String());
    }

    public function test_cancelling_deletes_an_empty_note(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->applyAndConfirm($course);

        app(HighTicketBookingService::class)->cancel(HighTicketLead::first());

        $this->assertSame(0, ConsultationNote::count());
    }

    public function test_cancelling_keeps_a_note_that_already_holds_a_record(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->applyAndConfirm($course);

        ConsultationNote::first()->update(['summary' => '## 客戶背景
- 已經談過了']);

        app(HighTicketBookingService::class)->cancel(HighTicketLead::first());

        $this->assertSame(1, ConsultationNote::count());
        $this->assertStringContainsString('已經談過了', ConsultationNote::first()->summary);
    }

    // ── 以 email 歸戶（本表存在的理由）──

    public function test_notes_from_two_different_leads_group_under_one_email(): void
    {
        $first = $this->makeHighTicketCourse();
        $second = $this->makeHighTicketCourse();

        $this->applyAndConfirm($first, ['email' => 'repeat@example.com']);
        $this->applyAndConfirm($second, ['email' => 'repeat@example.com']);

        $this->assertSame(2, ConsultationNote::forEmail('repeat@example.com')->count());
        $this->assertSame(2, HighTicketLead::where('email', 'repeat@example.com')->first()->consultationNotes()->count());
    }

    public function test_lookup_by_email_is_case_insensitive(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->applyAndConfirm($course, ['email' => 'casing@example.com']);

        $this->assertSame(1, ConsultationNote::forEmail('CASING@Example.com')->count());
    }

    public function test_a_note_that_belongs_to_no_lead_still_groups_under_the_customer(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->applyAndConfirm($course, ['email' => 'coach@example.com']);

        // What the upcoming self-service coaching flow will write: no lead, no
        // course, just a customer and a session.
        ConsultationNote::create([
            'email'  => 'coach@example.com',
            'source' => 'coaching_service',
            'met_at' => now()->addWeek(),
        ]);

        $lead = HighTicketLead::where('email', 'coach@example.com')->first();

        $this->assertSame(2, $lead->consultationNotes()->count());
    }

    public function test_notes_are_listed_newest_first(): void
    {
        ConsultationNote::create(['email' => 'order@example.com', 'met_at' => now()->subMonth(), 'source' => 'x']);
        ConsultationNote::create(['email' => 'order@example.com', 'met_at' => now(), 'source' => 'x']);

        $lead = HighTicketLead::create([
            'name'      => 'Order',
            'email'     => 'order@example.com',
            'course_id' => $this->makeHighTicketCourse()->id,
            'booked_at' => now(),
        ]);

        $dates = $lead->consultationNotes()->pluck('met_at');

        $this->assertTrue($dates[0]->greaterThan($dates[1]));
    }

    // ── 補建（FR-119）──

    /**
     * The situation this exists for: bookings confirmed before US23 shipped.
     * Simulated by deleting the record the confirmation created.
     */
    public function test_backfill_creates_the_missing_note_for_a_confirmed_booking(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->applyAndConfirm($course);

        ConsultationNote::query()->delete();
        $this->assertSame(0, ConsultationNote::count());

        $this->artisan('booking:backfill-consultation-notes')->assertExitCode(0);

        $note = ConsultationNote::first();
        $this->assertNotNull($note);
        $this->assertSame('booker@example.com', $note->email);
        $this->assertSame($course->id, $note->course_id);
        $this->assertNotNull($note->met_at);
    }

    public function test_backfill_is_idempotent(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->applyAndConfirm($course);

        $this->artisan('booking:backfill-consultation-notes')->assertExitCode(0);
        $this->artisan('booking:backfill-consultation-notes')->assertExitCode(0);

        $this->assertSame(1, ConsultationNote::count());
    }

    public function test_backfill_dry_run_writes_nothing(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->applyAndConfirm($course);
        ConsultationNote::query()->delete();

        $this->artisan('booking:backfill-consultation-notes', ['--dry-run' => true])->assertExitCode(0);

        $this->assertSame(0, ConsultationNote::count());
    }

    /**
     * A cancelled booking has no meeting left to transcribe, and FR-114 already
     * decided its empty record should not exist.
     */
    public function test_backfill_skips_cancelled_bookings(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->applyAndConfirm($course);

        app(HighTicketBookingService::class)->cancel(HighTicketLead::first());
        $this->assertSame(0, ConsultationNote::count());

        $this->artisan('booking:backfill-consultation-notes')->assertExitCode(0);

        $this->assertSame(0, ConsultationNote::count());
    }
}
