<?php

namespace Tests\Feature\HighTicket;

use App\Models\ConsultationSlot;
use App\Models\Course;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\HighTicketBookingService;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\BooksHighTicket;
use Tests\TestCase;

/**
 * 011 US16 — one booking per person per course.
 *
 * The bug this closes was not "duplicates appear": it was that a second
 * application silently tore down the first confirmed one (`confirmed_at` reset,
 * slot released) with nobody told, so the assertions below care as much about
 * what must NOT change as about the 422.
 */
class DuplicateBookingTest extends TestCase
{
    use BooksHighTicket, RefreshDatabase;

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

    private function confirmationTemplate(): void
    {
        EmailTemplate::updateOrCreate(['event_type' => 'high_ticket_booking_confirmation'], [
            'name' => '預約確認', 'event_type' => 'high_ticket_booking_confirmation',
            'subject' => '{{course_name}} 預約確認', 'body_md' => '時段 {{slot_time}}',
        ]);
    }

    /** A confirmed booking, then whatever status the case needs. */
    private function bookedLead(Course $course, string $status = 'pending'): HighTicketLead
    {
        $this->confirmationTemplate();
        Mail::fake();

        $this->applyAndConfirm($course);
        $lead = HighTicketLead::firstOrFail();

        if ($status !== 'pending') {
            $lead->update(['status' => $status]);
        }

        return $lead->fresh();
    }

    /** A free start well away from the seeded 10:00 block. */
    private function anotherStart(): string
    {
        $at = $this->seedSlots()->copy()->addHours(3);

        for ($i = 0; $i < 4; $i++) {
            ConsultationSlot::firstOrCreate(['starts_at' => $at->copy()->addMinutes($i * 15)]);
        }

        return $at->toIso8601String();
    }

    // -------------------------------------------------- phone normalisation

    /** @dataProvider phoneVariants */
    public function test_taiwanese_numbers_normalise_to_one_form(string $raw): void
    {
        $this->assertSame('0912345678', PhoneNumber::normalise($raw));
    }

    public static function phoneVariants(): array
    {
        return [
            ['0912345678'],
            ['0912-345-678'],
            ['0912 345 678'],
            ['+886912345678'],
            ['886912345678'],
            ['(0912) 345-678'],
            ['  0912345678  '],
        ];
    }

    public function test_blank_input_normalises_to_null(): void
    {
        $this->assertNull(PhoneNumber::normalise(null));
        $this->assertNull(PhoneNumber::normalise(''));
        $this->assertNull(PhoneNumber::normalise('   '));
        $this->assertNull(PhoneNumber::normalise('---'));
    }

    /** Not Taiwan: digits only is still a stable comparison key (D62). */
    public function test_a_foreign_number_degrades_to_digits(): void
    {
        $this->assertSame('16505551234', PhoneNumber::normalise('+1 (650) 555-1234'));
    }

    public function test_the_stored_phone_is_normalised(): void
    {
        Mail::fake();

        $this->postJson("/course/{$this->makeHighTicketCourse()->id}/book", $this->applicationData([
            'phone' => '0912-345-678',
        ]))->assertOk();

        $this->assertSame('0912345678', HighTicketLead::firstOrFail()->phone);
    }

    // ------------------------------------------------------------- blocking

    /** @dataProvider blockedStatuses */
    public function test_a_live_lead_blocks_a_second_application(string $status): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->bookedLead($course, $status);
        $held = $lead->slots()->pluck('starts_at')->all();

        $response = $this->postJson("/course/{$course->id}/book", $this->applicationData([
            'slot_starts_at' => $this->anotherStart(),
        ]))->assertStatus(422);

        $this->assertStringContainsString('已經預約', $response->json('message'));

        // Nothing may move: this is exactly what used to be destroyed.
        $lead->refresh();
        $this->assertNotNull($lead->confirmed_at, '既有預約的確認狀態不可被清掉');
        $this->assertSame($status, $lead->status);
        $this->assertEquals($held, $lead->slots()->pluck('starts_at')->all(), '既有時段不可被釋放或移動');
        $this->assertSame(1, HighTicketLead::count());
    }

    public static function blockedStatuses(): array
    {
        return [
            '等待面談' => ['pending'],
            '已面談'   => ['contacted'],
            '已成交'   => ['converted'],
            '未出席'   => ['no_response'],
        ];
    }

    /** @dataProvider allowedStatuses */
    public function test_a_finished_lead_may_apply_again(string $status): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->bookedLead($course, $status);

        if ($status === 'cancelled') {
            $lead->update(['cancelled_at' => now()]);
        }

        $this->postJson("/course/{$course->id}/book", $this->applicationData([
            'slot_starts_at' => $this->anotherStart(),
        ]))->assertOk();
    }

    public static function allowedStatuses(): array
    {
        return [
            '已關閉' => ['closed'],
            '已取消' => ['cancelled'],
        ];
    }

    /**
     * FR-065: status decides before confirmed_at. A `closed` lead almost always
     * has confirmed_at set (you talk first, then it goes cold) — reading the
     * timestamp first would block every re-engagement.
     */
    public function test_a_closed_lead_with_a_past_confirmation_is_not_blocked(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->bookedLead($course, 'closed');

        $this->assertNotNull($lead->confirmed_at, '前提：closed 的 lead 確實帶著 confirmed_at');

        $this->postJson("/course/{$course->id}/book", $this->applicationData([
            'slot_starts_at' => $this->anotherStart(),
        ]))->assertOk();
    }

    /** Mid-application is not 等待面談 — changing your mind about the slot is fine. */
    public function test_an_unconfirmed_application_may_be_resubmitted(): void
    {
        Mail::fake();
        $course = $this->makeHighTicketCourse();
        $this->applyForBooking($course);

        $this->postJson("/course/{$course->id}/book", $this->applicationData([
            'slot_starts_at' => $this->anotherStart(),
        ]))->assertOk();
    }

    public function test_a_different_email_with_the_same_phone_is_blocked(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->bookedLead($course);

        $this->postJson("/course/{$course->id}/book", $this->applicationData([
            'email'          => 'someone-else@example.com',
            'phone'          => '0912-345-678',
            'slot_starts_at' => $this->anotherStart(),
        ]))->assertStatus(422);

        $this->assertSame(1, HighTicketLead::count());
    }

    public function test_a_different_phone_with_the_same_email_is_blocked(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->bookedLead($course);

        $this->postJson("/course/{$course->id}/book", $this->applicationData([
            'phone'          => '0987654321',
            'slot_starts_at' => $this->anotherStart(),
        ]))->assertStatus(422);
    }

    public function test_another_course_is_unaffected(): void
    {
        $first = $this->makeHighTicketCourse();
        $this->bookedLead($first);
        $second = $this->makeHighTicketCourse();

        $this->postJson("/course/{$second->id}/book", $this->applicationData([
            'slot_starts_at' => $this->anotherStart(),
        ]))->assertOk();
    }

    // -------------------------------------------------------- the message

    public function test_the_message_names_the_assigned_consultant(): void
    {
        $consultant = User::factory()->create([
            'email' => 'consultant@example.com',
            'is_sales_consultant' => true,
        ]);
        $course = $this->makeHighTicketCourse();
        $lead = $this->bookedLead($course);
        $lead->update(['consultant_id' => $consultant->id]);

        $response = $this->postJson("/course/{$course->id}/book", $this->applicationData([
            'slot_starts_at' => $this->anotherStart(),
        ]))->assertStatus(422);

        $this->assertStringContainsString('consultant@example.com', $response->json('message'));
    }

    public function test_without_a_consultant_the_message_names_support(): void
    {
        SiteSetting::set(SiteSetting::SUPPORT_EMAIL_KEY, 'help@example.com');
        $course = $this->makeHighTicketCourse();
        $this->bookedLead($course);

        $response = $this->postJson("/course/{$course->id}/book", $this->applicationData([
            'slot_starts_at' => $this->anotherStart(),
        ]))->assertStatus(422);

        $this->assertStringContainsString('help@example.com', $response->json('message'));
    }

    public function test_the_waitlist_route_is_blocked_too(): void
    {
        $course = $this->makeHighTicketCourse();
        $this->bookedLead($course);

        // Build the payload first — applicationData() seeds slots as a side
        // effect, and leaving them in place would trip the "slots do exist"
        // guard instead, passing for the wrong reason.
        $payload = $this->applicationData(['slot_starts_at' => null]);
        ConsultationSlot::query()->delete();

        $response = $this->postJson("/course/{$course->id}/waitlist", $payload)->assertStatus(422);

        $this->assertStringContainsString('已經預約', $response->json('message'));
    }
}
