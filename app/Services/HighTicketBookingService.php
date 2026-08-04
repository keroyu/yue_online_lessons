<?php

namespace App\Services;

use App\Exceptions\SlotUnavailableException;
use App\Mail\TemplatedMail;
use App\Models\Course;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use App\Models\SiteSetting;
use Carbon\CarbonInterface;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class HighTicketBookingService
{
    /**
     * Fallback recipients copied on every booking confirmation — a new lead has
     * to reach a human even if nobody is watching the admin panel. Editable in
     * 後台 → Email 模板管理; this list is only what an unconfigured site uses
     * (the customer-service address used on the payment and legal pages is a
     * different role, not a lead recipient).
     */
    public const DEFAULT_NOTIFY_CC = [
        'themustbig+leads@gmail.com',  // 管理員
    ];

    public const NOTIFY_CC_SETTING_KEY = 'high_ticket_lead_notify_cc';

    /** How long a slot stays reserved while we wait for the emailed click. */
    public const HOLD_MINUTES = 60;

    public function __construct(protected ConsultationSlotService $slots) {}

    /**
     * Stage one of a booking (011 US9–US11 / FR-033).
     *
     * Submitting the wizard does NOT make a booking: it records the application,
     * holds the chosen slot for an hour and emails a confirmation link. The drip
     * stop and the CAPI Lead event deliberately do not fire here — an
     * unverified email is not yet a lead (D35).
     *
     * @throws SlotUnavailableException when somebody took the slot first
     * @return array{success: bool, message?: string, mail_sent?: bool, hold_expires_at?: string}
     */
    public function apply(Course $course, array $data): array
    {
        if (!$course->is_high_ticket || !$course->high_ticket_hide_price) {
            return ['success' => false, 'message' => '此課程不接受預約'];
        }

        $template = EmailTemplate::forEvent('high_ticket_booking_verify')->first();

        if (!$template) {
            return ['success' => false, 'message' => '預約待確認信模板不存在，請聯絡管理員'];
        }

        $code = $data['code'] ?? null;
        $minutes = $this->slots->minutesFor($code);
        $startsAt = Carbon::parse($data['slot_starts_at'])->utc();
        $expiresAt = now()->addMinutes(self::HOLD_MINUTES);

        // The lead and the hold are one fact: a lead that thinks it booked a slot
        // it does not hold is worse than no lead at all (FR-032).
        $lead = DB::transaction(function () use ($course, $data, $code, $minutes, $startsAt, $expiresAt) {
            $lead = $this->recordLead($course, $data, $code, $expiresAt);
            $this->slots->reserve($lead, $startsAt, $minutes, $expiresAt);

            return $lead;
        });

        $mailSent = $this->sendVerifyMail($lead, $course, $template, $startsAt, $minutes, $expiresAt);

        // Nobody can click a link they never received, so holding the slot would
        // only lock out people whose mail does arrive (D34).
        if (!$mailSent) {
            $this->slots->release($lead);
        }

        return [
            'success'         => true,
            'mail_sent'       => $mailSent,
            'hold_expires_at' => $expiresAt->toIso8601String(),
            'slot_label'      => $this->slots->label($startsAt),
            'minutes'         => $minutes,
        ];
    }

    /**
     * No slots are open yet, but the application is still worth having (011 US10).
     *
     * Records the lead exactly as an application would, minus everything that
     * needs a slot: no hold, no token, no verify mail. The admin picks it up
     * with 「通知新時段」(US4) once availability exists.
     *
     * Guarded server-side: if slots ARE available this is refused, otherwise it
     * becomes a way to skip the picker entirely.
     *
     * @return array{success: bool, message?: string, waitlisted?: bool}
     */
    public function waitlist(Course $course, array $data): array
    {
        if (!$course->is_high_ticket || !$course->high_ticket_hide_price) {
            return ['success' => false, 'message' => '此課程不接受預約'];
        }

        if ($this->slots->availableStarts(ConsultationSlotService::DEFAULT_MINUTES) !== []) {
            return ['success' => false, 'message' => '目前有可預約的時段，請直接選擇時段完成申請'];
        }

        $lead = $this->recordLead($course, $data, $data['code'] ?? null, now());

        // Nothing to confirm, so that token would only be a dead link. The
        // resume token takes its place: 「通知新時段」 mails it back as a link
        // that reopens the wizard straight at the slot picker (FR-042). An
        // existing one is kept so links already sent out keep working.
        $lead->update([
            'confirm_token'      => null,
            'confirm_expires_at' => null,
            'resume_token'       => $lead->resume_token ?: Str::random(64),
        ]);

        return ['success' => true, 'waitlisted' => true];
    }

    /**
     * Stage two: the emailed link was clicked (011 US11).
     *
     * Only now does the booking exist — the slot becomes permanent, the real
     * confirmation email goes out, the drip sequence stops and Meta hears about
     * the lead.
     *
     * @return array{state: string, lead?: HighTicketLead, course?: Course, slot_label?: string, minutes?: int}
     */
    public function confirm(string $token): array
    {
        $lead = HighTicketLead::where('confirm_token', $token)->first();

        if (!$lead) {
            return ['state' => 'invalid'];
        }

        $course = Course::find($lead->course_id);
        $slot = $lead->slots()->first();
        $context = [
            'lead'       => $lead,
            'course'     => $course,
            'slot_label' => $slot ? $this->slots->label($slot->starts_at) : null,
        ];

        if ($lead->confirmed_at !== null) {
            return array_merge(['state' => 'already'], $context);
        }

        if ($lead->confirm_expires_at === null || $lead->confirm_expires_at->isPast()) {
            return array_merge(['state' => 'expired'], $context);
        }

        DB::transaction(function () use ($lead) {
            $lead->update(['confirmed_at' => now()]);
            $this->slots->confirm($lead);
        });

        // External effects stay outside the transaction and never block the
        // confirmation that already happened (比照 FR-016).
        $this->afterConfirmation($lead, $course, $slot?->starts_at);

        return array_merge(['state' => 'confirmed'], $context);
    }

    /**
     * Everything that follows a confirmed booking. Each step is best-effort:
     * the visitor has already done their part.
     */
    private function afterConfirmation(HighTicketLead $lead, ?Course $course, ?Carbon $startsAt): void
    {
        if (!$course) {
            return;
        }

        $this->createMeetingAndConfirm($lead, $course, $startsAt);

        try {
            app(DripService::class)->checkAndBook($lead->email, $course);
        } catch (\Exception $e) {
            Log::error('High ticket booking: drip stop failed', [
                'email' => $lead->email,
                'course_id' => $course->id,
                'error' => $e->getMessage(),
            ]);
        }

        // A confirmed booking is the Lead conversion for ad optimization (000 US7).
        try {
            $meta = app(MetaConversionsService::class);
            $meta->send('Lead', array_merge($meta->userDataFromRequest(request()), [
                'em' => $meta->hashEmail($lead->email),
            ]), [
                'content_ids'  => [$course->id],
                'content_type' => 'product',
                'content_name' => $course->name,
            ]);
        } catch (\Exception $e) {
            Log::error('High ticket booking: CAPI lead failed', [
                'email' => $lead->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build the Zoom meeting (if configured) and send the one confirmation mail
     * that carries its link — both inline (D55).
     *
     * This used to run in a queued job so the API call would not sit in the
     * visitor's request. The mail went with it, which made the confirmation the
     * only visitor-facing path in this module whose outcome depended on a queue
     * worker being alive — and its failure was silent: the page says 相關資料已寄出
     * and nothing ever arrives. Inline, the worst case is a slower page and a
     * mail without a link, both of which are visible.
     *
     * Zoom failing MUST NOT cost the applicant the confirmation (FR-038).
     */
    private function createMeetingAndConfirm(HighTicketLead $lead, Course $course, ?Carbon $startsAt): void
    {
        $zoom = app(ZoomMeetingService::class);

        if (!$zoom->isEnabled() || !$startsAt) {
            $this->sendConfirmationMail($lead, $course);

            return;
        }

        try {
            $meeting = $zoom->createMeeting(
                $startsAt,
                $this->slots->minutesFor($lead->booking_code),
                "{$course->name} 1v1 諮詢 - {$lead->name}"
            );

            $lead->update([
                'zoom_meeting_id' => $meeting['meeting_id'],
                'zoom_join_url'   => $meeting['join_url'],
            ]);

            $this->sendConfirmationMail($lead, $course, $meeting['join_url']);
        } catch (\Exception $e) {
            // The booking is a settled fact — the applicant confirmed and the
            // slot is theirs — so the confirmation still goes out, just without
            // a link, and the internal recipients are told to open one by hand
            // (D39).
            Log::error('High ticket booking: Zoom meeting could not be created, confirming without a link', [
                'lead_id' => $lead->id,
                'error'   => $e->getMessage(),
            ]);

            $this->sendConfirmationMail($lead, $course, '（會議連結將另行寄出）');
        }
    }

    /**
     * The real "客製服務預約確認" mail (FR-038).
     */
    public function sendConfirmationMail(HighTicketLead $lead, Course $course, ?string $zoomUrl = null, array $extraCc = []): bool
    {
        $template = EmailTemplate::forEvent('high_ticket_booking_confirmation')->first();

        if (!$template) {
            Log::warning('High ticket booking: confirmation template missing', ['lead_id' => $lead->id]);

            return false;
        }

        $slot = $lead->slots()->first();

        $vars = [
            '{{user_name}}'       => $lead->name,
            '{{user_email}}'      => $lead->email,
            '{{course_name}}'     => $course->name,
            '{{slot_time}}'       => $slot ? $this->slots->label($slot->starts_at) : '',
            '{{consult_minutes}}' => (string) $this->slots->minutesFor($lead->booking_code),
            '{{zoom_join_url}}'   => $zoomUrl ?? ($lead->zoom_join_url ?? ''),
        ];

        // The calendar entry goes out whether or not Zoom produced a link: the
        // time is the thing that has to reach their calendar, the link is extra
        // (FR-046). Without this the applicant is left copying a date out of an
        // email by hand, which is where no-shows come from.
        $attachments = $slot
            ? [$this->inviteAttachment($lead, $course, $slot->starts_at, $vars['{{zoom_join_url}}'] ?: null)]
            : [];

        try {
            Mail::to($lead->email)
                ->cc(array_values(array_unique(array_merge($this->notifyCc(), $extraCc))))
                ->send(new TemplatedMail(
                    $template->renderSubject($vars),
                    $template->renderBody($vars),
                    $template->renderText($vars),
                    $attachments,
                ));

            return true;
        } catch (\Exception $e) {
            Log::error('High ticket booking confirmation email failed', [
                'lead_id' => $lead->id,
                'error'   => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Move a confirmed booking to another slot (011 US14 / FR-048).
     *
     * Admin-only by design (D50): there is no self-service path, so this is
     * always somebody acting on a message the applicant sent them.
     *
     * @throws SlotUnavailableException when the target range is taken
     * @return array{success: bool, message?: string, slot_label?: string}
     */
    public function reschedule(HighTicketLead $lead, CarbonInterface $newStartsAt): array
    {
        if (!$lead->isActiveBooking()) {
            return ['success' => false, 'message' => '這筆預約尚未確認或已取消，無法改期'];
        }

        $course = Course::find($lead->course_id);

        if (!$course) {
            return ['success' => false, 'message' => '找不到對應的課程'];
        }

        $oldSlot = $lead->slots()->first();
        $oldLabel = $oldSlot ? $this->slots->label($oldSlot->starts_at) : '';
        $minutes = $this->slots->minutesFor($lead->booking_code);
        $newStart = Carbon::instance($newStartsAt)->utc();

        // reserve() drops this lead's own units before it checks availability,
        // so moving 10:00 to 10:15 is not blocked by the booking itself. A null
        // hold books the new range outright — it was already confirmed.
        $this->slots->reserve($lead, $newStart, $minutes, null);

        $lead->increment('calendar_sequence');
        $lead->refresh();

        // Outside the slot move, and each independently non-fatal: the schedule
        // has already changed, and neither a mail server nor Zoom gets a vote on
        // that (沿用 FR-016).
        $this->sendChangeMail($lead, $course, 'high_ticket_booking_rescheduled', [
            '{{old_slot_time}}' => $oldLabel,
            '{{slot_time}}'     => $this->slots->label($newStart),
        ], $this->inviteAttachment($lead, $course, $newStart, $lead->zoom_join_url ?: null));

        $zoomSynced = $this->syncZoom(
            fn (ZoomMeetingService $zoom) => $zoom->updateMeeting($lead->zoom_meeting_id, $newStart, $minutes),
            $lead->zoom_meeting_id,
            $lead,
            'update'
        );

        return [
            'success'     => true,
            'slot_label'  => $this->slots->label($newStart),
            'zoom_synced' => $zoomSynced,
        ];
    }

    /**
     * Call the booking off (011 US14 / FR-049).
     *
     * The token columns survive: a cancelled lead who changes their mind is the
     * same person, and `recordLead()` treats `cancelled` as revivable, so links
     * already in their inbox keep working.
     *
     * @return array{success: bool, message?: string}
     */
    public function cancel(HighTicketLead $lead): array
    {
        if (!$lead->isActiveBooking()) {
            return ['success' => false, 'message' => '這筆預約尚未確認或已取消'];
        }

        $course = Course::find($lead->course_id);
        $slot = $lead->slots()->first();
        $startsAt = $slot?->starts_at?->copy();
        $meetingId = (string) ($lead->zoom_meeting_id ?? '');

        DB::transaction(function () use ($lead) {
            $this->slots->release($lead);

            $lead->update([
                'cancelled_at'    => now(),
                'status'          => 'cancelled',
                'zoom_meeting_id' => null,
                'zoom_join_url'   => null,
            ]);
        });

        $lead->increment('calendar_sequence');
        $lead->refresh();

        if ($course && $startsAt) {
            $this->sendChangeMail($lead, $course, 'high_ticket_booking_cancelled', [
                '{{slot_time}}'  => $this->slots->label($startsAt),
                '{{course_url}}' => $this->courseUrl($course),
            ], $this->cancellationAttachment($lead, $course, $startsAt));
        }

        $zoomSynced = $this->syncZoom(
            fn (ZoomMeetingService $zoom) => $zoom->deleteMeeting($meetingId),
            $meetingId,
            $lead,
            'delete'
        );

        return ['success' => true, 'zoom_synced' => $zoomSynced];
    }

    /**
     * Push a booking change through to Zoom, inline (D55).
     *
     * Tri-state on purpose: null when there was nothing to sync (Zoom off, or
     * the booking never had a meeting), true on success, false when the call
     * failed. The caller only warns on false — "no meeting existed" is not a
     * failure, and warning about it would train the admin to ignore the notice.
     *
     * Never throws: the slots have already moved and the applicant already has
     * the mail, so a Zoom outage MUST NOT undo a settled fact (FR-050).
     */
    private function syncZoom(callable $call, ?string $meetingId, HighTicketLead $lead, string $action): ?bool
    {
        $zoom = app(ZoomMeetingService::class);

        if (!$zoom->isEnabled() || blank($meetingId)) {
            return null;
        }

        try {
            $call($zoom);

            return true;
        } catch (\Exception $e) {
            Log::error('High ticket booking: Zoom is now out of step with the booking', [
                'lead_id'    => $lead->id,
                'meeting_id' => $meetingId,
                'action'     => $action,
                'error'      => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Shared body of the two change mails. A missing template is logged rather
     * than thrown: the booking has already moved, and refusing to finish because
     * nobody seeded a template would leave the schedule and the applicant's
     * calendar disagreeing.
     *
     * @param array<string, string> $extraVars
     */
    private function sendChangeMail(
        HighTicketLead $lead,
        Course $course,
        string $eventType,
        array $extraVars,
        Attachment $attachment
    ): bool {
        $template = EmailTemplate::forEvent($eventType)->first();

        if (!$template) {
            Log::warning('High ticket booking: change template missing', [
                'lead_id'    => $lead->id,
                'event_type' => $eventType,
            ]);

            return false;
        }

        $vars = array_merge([
            '{{user_name}}'       => $lead->name,
            '{{user_email}}'      => $lead->email,
            '{{course_name}}'     => $course->name,
            '{{consult_minutes}}' => (string) $this->slots->minutesFor($lead->booking_code),
            '{{zoom_join_url}}'   => $lead->zoom_join_url ?? '',
        ], $extraVars);

        try {
            Mail::to($lead->email)
                ->cc($this->notifyCc())
                ->send(new TemplatedMail(
                    $template->renderSubject($vars),
                    $template->renderBody($vars),
                    $template->renderText($vars),
                    [$attachment],
                ));

            return true;
        } catch (\Exception $e) {
            Log::error('High ticket booking change email failed', [
                'lead_id'    => $lead->id,
                'event_type' => $eventType,
                'error'      => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function inviteAttachment(HighTicketLead $lead, Course $course, CarbonInterface $startsAt, ?string $zoomUrl): Attachment
    {
        $ics = app(CalendarInviteService::class)->invite(
            $lead,
            $course,
            $startsAt,
            $this->slots->minutesFor($lead->booking_code),
            $zoomUrl
        );

        return $this->calendarAttachment($ics, 'REQUEST');
    }

    private function cancellationAttachment(HighTicketLead $lead, Course $course, CarbonInterface $startsAt): Attachment
    {
        $ics = app(CalendarInviteService::class)->cancellation(
            $lead,
            $course,
            $startsAt,
            $this->slots->minutesFor($lead->booking_code)
        );

        return $this->calendarAttachment($ics, 'CANCEL');
    }

    /**
     * The `method=` parameter is what makes a client treat the file as an
     * invitation or a withdrawal rather than a plain attachment — it has to
     * match the METHOD line inside the file (FR-053).
     */
    private function calendarAttachment(string $ics, string $method): Attachment
    {
        return Attachment::fromData(fn () => $ics, 'consultation.ics')
            ->withMime("text/calendar; charset=UTF-8; method={$method}");
    }

    private function courseUrl(Course $course): string
    {
        return rtrim((string) config('app.url'), '/') . '/course/' . ($course->slug ?: $course->id);
    }

    private function sendVerifyMail(
        HighTicketLead $lead,
        Course $course,
        EmailTemplate $template,
        Carbon $startsAt,
        int $minutes,
        Carbon $expiresAt
    ): bool {
        $vars = [
            '{{user_name}}'   => $lead->name,
            '{{user_email}}'  => $lead->email,
            '{{course_name}}' => $course->name,
            '{{confirm_url}}' => url("/booking/confirm/{$lead->confirm_token}"),
            '{{slot_time}}'   => $this->slots->label($startsAt) . "（{$minutes} 分鐘）",
            '{{expires_at}}'  => $expiresAt->timezone(ConsultationSlotService::DISPLAY_TZ)->format('H:i'),
        ];

        try {
            Mail::to($lead->email)
                ->cc($this->notifyCc())
                ->send(new TemplatedMail(
                    $template->renderSubject($vars),
                    $template->renderBody($vars),
                    $template->renderText($vars),
                ));

            return true;
        } catch (\Exception $e) {
            Log::error('High ticket booking verify email failed', [
                'email' => $lead->email,
                'course_id' => $course->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Re-applying for the same course with the same email is the same person,
     * not a second lead — refresh the existing row so the follow-up history
     * stays in one place (D17). A lead that was closed (cold / moved to the drip
     * sequence) or silent (no_response) is live again — re-applying IS the
     * response; contacted and converted keep whatever the admin set.
     *
     * A fresh token is minted every time, which invalidates any link from a
     * previous attempt — otherwise an abandoned application could confirm a
     * slot the applicant no longer holds.
     */
    private function recordLead(Course $course, array $data, ?string $code, Carbon $expiresAt): HighTicketLead
    {
        $application = [
            'name'                    => $data['name'],
            'phone'                   => $data['phone'] ?? null,
            'occupation'              => $data['occupation'] ?? null,
            'bottleneck'              => $data['bottleneck'] ?? null,
            'expertise'               => $data['expertise'] ?? null,
            'social_url'              => $data['social_url'] ?? null,
            'commitments_accepted_at' => now(),
            'booking_code'            => $this->slots->codeIsValid($code) ? $code : null,
            'confirm_token'           => Str::random(64),
            'confirm_expires_at'      => $expiresAt,
            'confirmed_at'            => null,
            'booked_at'               => now(),
        ];

        $lead = HighTicketLead::where('email', $data['email'])
            ->where('course_id', $course->id)
            ->latest('id')
            ->first();

        if (!$lead) {
            return HighTicketLead::create(array_merge($application, [
                'email'     => $data['email'],
                'course_id' => $course->id,
                'status'    => 'pending',
            ]));
        }

        $lead->update(array_merge($application, [
            // 'cancelled' joins the revivable set (FR-049): somebody re-applying
            // after calling one off is exactly the re-engagement we wanted.
            'status'       => in_array($lead->status, ['closed', 'no_response', 'cancelled'], true) ? 'pending' : $lead->status,
            'cancelled_at' => null,
        ]));

        return $lead;
    }

    /**
     * @return array<int, string>
     */
    public function notifyCc(): array
    {
        $configured = self::parseRecipients((string) SiteSetting::get(self::NOTIFY_CC_SETTING_KEY, ''));

        return $configured ?: self::DEFAULT_NOTIFY_CC;
    }

    /**
     * Split an admin-entered recipient list (comma / whitespace separated).
     *
     * @return array<int, string>
     */
    public static function parseRecipients(string $raw): array
    {
        $parts = preg_split('/[,;\s]+/', trim($raw)) ?: [];

        return array_values(array_unique(array_filter($parts, fn ($p) => $p !== '')));
    }
}
