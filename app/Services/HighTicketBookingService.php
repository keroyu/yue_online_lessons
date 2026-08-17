<?php

namespace App\Services;

use App\Exceptions\SlotUnavailableException;
use App\Mail\BookingVerifyMail;
use App\Mail\TemplatedMail;
use App\Models\ConsultationNote;
use App\Models\ConsultationSlot;
use App\Models\Course;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\BookingScreening;
use App\Support\PhoneNumber;
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

    /**
     * How long an abandoned application is left alone before the nudge (US26).
     *
     * Three hours: long enough that somebody who stepped away mid-form is not
     * emailed about it while they are still in the tab, short enough that the
     * reason they applied is still on their mind.
     */
    public const RESUME_REMINDER_AFTER_HOURS = 3;

    /** Nothing older than this is nudged — switching the feature on must not mail the archive. */
    public const RESUME_REMINDER_MAX_AGE_DAYS = 7;

    public function __construct(protected ConsultationSlotService $slots) {}

    /**
     * Step one of the wizard: the five-question gate (011 US24 / FR-125).
     *
     * Records the answers whatever the verdict — the output of this step IS
     * "which kind of person is this", and the people who walk away halfway are
     * exactly the ones worth having on file. Nothing is emailed, no slot is
     * touched, and a refusal deliberately does NOT stop the drip sequence these
     * applicants arrived from (FR-126).
     *
     * @return array{success: bool, message?: string, passed?: bool}
     */
    public function screen(Course $course, array $data): array
    {
        if (!$course->is_high_ticket || !$course->high_ticket_hide_price) {
            return ['success' => false, 'message' => '此課程不接受預約'];
        }

        $answers = BookingScreening::only($data);
        $passed = BookingScreening::passes($answers);

        $attributes = array_merge($answers, [
            'name'            => $data['name'],
            'screening_score' => BookingScreening::score($answers),
            'screened_at'     => now(),
        ]);

        $lead = HighTicketLead::where('email', $data['email'])
            ->where('course_id', $course->id)
            ->latest('id')
            ->first();

        if (!$lead) {
            HighTicketLead::create(array_merge($attributes, [
                'email'       => $data['email'],
                'course_id'   => $course->id,
                'status'      => $passed ? 'pending' : 'declined',
                'declined_at' => $passed ? null : now(),
                'booked_at'   => now(),
            ]));

            return ['success' => true, 'passed' => $passed];
        }

        // Whose status this is allowed to rewrite. A confirmed booking or a
        // status an admin moved by hand is somebody's work in progress —
        // re-answering a questionnaire must never undo it (FR-125).
        if ($lead->confirmed_at === null && in_array($lead->status, ['pending', 'declined'], true)) {
            $attributes['status'] = $passed ? 'pending' : 'declined';
            $attributes['declined_at'] = $passed ? null : now();

            // An application that was mid-flight and just failed a re-screen
            // would otherwise keep its hold and a live confirmation link —
            // a declined lead holding a slot is the one shape FR-126 forbids.
            if (!$passed) {
                $this->slots->release($lead);
                $attributes['confirm_token'] = null;
                $attributes['confirm_expires_at'] = null;
            }
        }

        $lead->update($attributes);

        return ['success' => true, 'passed' => $passed];
    }

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

        if ($blocked = $this->duplicateMessage($course, $data)) {
            return ['success' => false, 'message' => $blocked];
        }

        if ($refused = $this->screeningRefusal($data)) {
            return $refused;
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

        $mailSent = $this->sendVerifyMail($lead, $course, $startsAt, $minutes, $expiresAt);

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
     * Drop applications whose confirmation hour ran out (011 FR-068).
     *
     * Releasing the slot was never the whole cleanup: the lead row stayed, so
     * the Leads list filled up with people who filled in the wizard and then
     * never clicked the emailed link. Those are not bookings and not anybody's
     * follow-up, so they leave no trace.
     *
     * Two guards decide what survives:
     *  - `confirmed_at` — a booking that was ever confirmed keeps its history,
     *    including one later cancelled.
     *  - `status = pending` — the moment an admin moves the lead anywhere else
     *    they are working it by hand, and a sweeper must not delete that.
     *
     * Waitlist entries carry no `confirm_expires_at` at all (they had nothing
     * to confirm), so they fall outside this entirely.
     *
     * Holds are released first: `consultation_slots.lead_id` has no foreign
     * key, so deleting in the other order would leave units pointing at a row
     * that no longer exists and the admin calendar would show a phantom owner.
     */
    public function purgeExpiredApplications(): int
    {
        $this->slots->releaseExpired();

        return HighTicketLead::whereNull('confirmed_at')
            ->whereNotNull('confirm_expires_at')
            ->where('confirm_expires_at', '<=', now())
            ->where('status', 'pending')
            ->delete();
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

        if ($blocked = $this->duplicateMessage($course, $data)) {
            return ['success' => false, 'message' => $blocked];
        }

        if ($refused = $this->screeningRefusal($data)) {
            return $refused;
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

        // The CRM record for this session, created now rather than when the
        // transcript arrives (FR-113) — the admin panel should show "已排定"
        // before the meeting happens, not only after it.
        try {
            $this->recordConsultationNote($lead, $course, $startsAt);
        } catch (\Exception $e) {
            Log::error('High ticket booking: consultation note could not be created', [
                'lead_id' => $lead->id,
                'error'   => $e->getMessage(),
            ]);
        }

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
                "{$lead->name} 諮詢",
                // Falls back to the owner's account when the consultant has no
                // Zoom seat yet (FR-063).
                $lead->consultant_id ? \App\Models\User::whereKey($lead->consultant_id)->value('email') : null
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
     * Create (or move) this booking's consultation record (011 US23 / FR-113).
     *
     * Matched on `zoom_meeting_id` when there is one so a re-confirmation moves
     * the existing row instead of producing a duplicate; without Zoom it falls
     * back to this lead's own row.
     *
     * Public because `booking:backfill-consultation-notes` calls it too: bookings
     * confirmed before US23 shipped never ran through here, and their meetings
     * are still ahead of them. That idempotent match above is what makes the
     * backfill safe to run more than once.
     */
    public function recordConsultationNote(HighTicketLead $lead, Course $course, ?Carbon $startsAt): void
    {
        if (!$startsAt) {
            return;
        }

        $lead->refresh();
        $meetingId = trim((string) ($lead->zoom_meeting_id ?? ''));

        $existing = $meetingId !== ''
            ? ConsultationNote::where('zoom_meeting_id', $meetingId)->first()
            : ConsultationNote::where('lead_id', $lead->id)->whereNull('zoom_meeting_id')->first();

        $attributes = [
            'email'           => $lead->email,
            'source'          => ConsultationNote::SOURCE_HIGH_TICKET,
            'lead_id'         => $lead->id,
            'user_id'         => User::where('email', $lead->email)->value('id'),
            'consultant_id'   => $lead->consultant_id,
            'course_id'       => $course->id,
            'met_at'          => Carbon::instance($startsAt)->utc(),
            'zoom_meeting_id' => $meetingId !== '' ? $meetingId : null,
        ];

        $existing ? $existing->update($attributes) : ConsultationNote::create($attributes);
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
                ->cc($this->confirmationCc($lead, $extraCc))
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
     * Email everyone whose consultation is tomorrow (011 US19 / FR-077).
     *
     * "Tomorrow" is a Taipei date on a server that runs UTC, so the window is
     * built in Taipei and converted, never the other way round: Taipei 00:00 is
     * 16:00 the previous UTC day, and a UTC-shaped window quietly drops both
     * ends of the day.
     *
     * @return int reminders actually sent
     */
    public function sendDayBeforeReminders(): int
    {
        $tomorrow = Carbon::now(ConsultationSlotService::DISPLAY_TZ)->addDay()->startOfDay();
        $from = $tomorrow->copy()->utc();
        $until = $tomorrow->copy()->addDay()->utc();

        $leadIds = ConsultationSlot::query()
            ->whereBetween('starts_at', [$from, $until->copy()->subSecond()])
            ->whereNotNull('lead_id')
            ->whereNull('held_until')
            ->distinct()
            ->pluck('lead_id');

        if ($leadIds->isEmpty()) {
            return 0;
        }

        $leads = HighTicketLead::query()
            ->whereIn('id', $leadIds)
            ->whereNotNull('confirmed_at')
            ->whereNull('cancelled_at')
            ->whereNull('reminder_sent_at')
            ->with('course')
            ->get();

        $sent = 0;

        foreach ($leads as $lead) {
            $first = $lead->slots()->first();

            // Anchor on the earliest unit: a consultation that starts tonight
            // and runs past midnight belongs to today, and yesterday's run
            // already had its chance at it (FR-077).
            if (!$first || $first->starts_at < $from || $first->starts_at >= $until) {
                continue;
            }

            if (!$lead->course) {
                Log::warning('High ticket reminder: lead has no course', ['lead_id' => $lead->id]);

                continue;
            }

            if ($this->sendReminderMail($lead, $lead->course, $first->starts_at)) {
                // Only after a successful send: writing it first would swallow
                // the lead whenever the mail server is having a bad minute.
                $lead->forceFill(['reminder_sent_at' => now()])->save();
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * The reminder itself — no CC and no .ics on purpose (FR-080 / D73).
     *
     * A second invite carrying the same UID and SEQUENCE is a no-op to every
     * calendar client, and bumping SEQUENCE just to be noticed would spend the
     * signal US14 uses to mean "this booking actually moved".
     */
    public function sendReminderMail(HighTicketLead $lead, Course $course, CarbonInterface $startsAt): bool
    {
        $template = EmailTemplate::forEvent('high_ticket_consultation_reminder')->first();

        if (!$template) {
            Log::warning('High ticket reminder: template missing', ['lead_id' => $lead->id]);

            return false;
        }

        $vars = [
            '{{user_name}}'       => $lead->name,
            '{{user_email}}'      => $lead->email,
            '{{course_name}}'     => $course->name,
            '{{slot_time}}'       => $this->slots->label($startsAt),
            '{{consult_minutes}}' => (string) $this->slots->minutesFor($lead->booking_code),
            '{{zoom_join_url}}'   => $lead->zoom_join_url ?? '',
        ];

        try {
            // The consultant is the other person who has to be there tomorrow,
            // and nothing else tells them so — the calendar invite went out at
            // confirmation, possibly weeks ago (FR-078, 2026-08-17 改). Same
            // recipient rule as the confirmation: the assigned consultant, or
            // the notify list when the slot has no owner (FR-062).
            Mail::to($lead->email)
                ->cc($this->confirmationCc($lead))
                ->send(new TemplatedMail(
                    $template->renderSubject($vars),
                    $template->renderBody($vars),
                    $template->renderText($vars),
                ));

            return true;
        } catch (\Exception $e) {
            // One bad address must not cost everybody else their reminder.
            Log::error('High ticket reminder email failed', [
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

        // A booking on another day is owed another reminder (011 D72). Nothing
        // is lost if the move lands after today's 17:00 run — the reschedule
        // mail on its way out carries the new time and an updated invite.
        $lead->forceFill(['reminder_sent_at' => null])->save();

        $lead->refresh();

        // Outside the slot move, and each independently non-fatal: the schedule
        // has already changed, and neither a mail server nor Zoom gets a vote on
        // that (沿用 FR-016).
        $this->sendChangeMail($lead, $course, 'high_ticket_booking_rescheduled', [
            '{{old_slot_time}}' => $oldLabel,
            '{{slot_time}}'     => $this->slots->label($newStart),
        ], $this->inviteAttachment($lead, $course, $newStart, $lead->zoom_join_url ?: null));

        // The record follows the booking; no history row (011 US23 / FR-114,
        // 沿用 US14 對 consultation_slots 的既有立場：只表達「現在」).
        ConsultationNote::where('lead_id', $lead->id)->update(['met_at' => $newStart]);

        $zoomSynced = $this->syncZoom(
            fn (ZoomMeetingService $zoom) => $zoom->updateMeeting($lead->zoom_meeting_id, $newStart, $minutes),
            $lead->zoom_meeting_id,
            $lead,
            'update'
        );

        return [
            'success'     => true,
            'slot_label'  => $this->slots->label($newStart),
            // Taipei date of the new slot, so the caller can send the admin to
            // the week that now holds this booking (US20 / FR-084).
            'slot_date'   => $newStart->copy()->timezone(ConsultationSlotService::DISPLAY_TZ)->format('Y-m-d'),
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

        // A cancelled session that was never held carries nothing worth keeping;
        // one that already has a transcript or summary does — the conversation
        // happened, only the follow-up was called off (011 FR-114).
        ConsultationNote::where('lead_id', $lead->id)
            ->get()
            ->each(fn (ConsultationNote $note) => $note->isEmpty()
                ? $note->delete()
                : $note->update(['zoom_meeting_id' => null]));

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
            // No CC (FR-062): a reschedule or cancellation is the outcome of a
            // conversation the admin was already part of.
            Mail::to($lead->email)
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

    /**
     * Deep link that reopens the wizard with this lead's answers (011 FR-042).
     *
     * Canonical here rather than in `NotifyHighTicketSlotJob`, which had the
     * only copy until US26 needed the same link: two builders means one of them
     * eventually points at a URL shape the wizard no longer honours.
     *
     * The token is minted lazily — a lead nobody ever writes to never needs one.
     */
    public function resumeUrl(HighTicketLead $lead): string
    {
        if (!$lead->resume_token) {
            $lead->update(['resume_token' => Str::random(64)]);
        }

        $course = $lead->course ?: Course::find($lead->course_id);
        $url = rtrim(config('app.url'), '/') . '/course/' . ($course?->slug ?: $lead->course_id);

        return $url . '?resume=' . $lead->resume_token;
    }

    /**
     * Nudge the applications that cleared the gate and then stopped (011 US26).
     *
     * These leads exist because screening records everybody at step 1 (FR-125),
     * and a good share of them are the most interesting rows in the table: they
     * answered five questions honestly, scored well, and then hit the field
     * asking them to write out their bottleneck. One mail, once, with a link
     * that puts them back where they left off.
     *
     * Deliberately narrow. `phone` is the marker for "never reached step 2" —
     * it is the first required field there, so anybody who has one got further
     * than this mail is for. And nothing older than a week: switching this on
     * must not mail every abandoned screening in the archive at once (FR-136).
     */
    public function sendApplicationResumeReminders(): int
    {
        $leads = HighTicketLead::query()
            ->whereNotNull('screened_at')
            ->where('screened_at', '<=', now()->subHours(self::RESUME_REMINDER_AFTER_HOURS))
            ->where('screened_at', '>=', now()->subDays(self::RESUME_REMINDER_MAX_AGE_DAYS))
            ->whereNull('resume_reminder_sent_at')
            ->whereNull('phone')
            ->whereNull('confirmed_at')
            ->where('status', 'pending')
            ->with('course')
            ->get();

        $sent = 0;

        foreach ($leads as $lead) {
            if (!$lead->course) {
                Log::warning('High ticket resume reminder: lead has no course', ['lead_id' => $lead->id]);

                continue;
            }

            if ($this->sendResumeReminderMail($lead, $lead->course)) {
                // Only after a successful send — the same rule as the day-before
                // reminder (FR-078), for the same reason.
                $lead->forceFill(['resume_reminder_sent_at' => now()])->save();
                $sent++;
            }
        }

        return $sent;
    }

    public function sendResumeReminderMail(HighTicketLead $lead, Course $course): bool
    {
        $template = EmailTemplate::forEvent('high_ticket_application_resume')->first();

        if (!$template) {
            Log::warning('High ticket resume reminder: template missing', ['lead_id' => $lead->id]);

            return false;
        }

        $vars = [
            '{{user_name}}'   => $lead->name,
            '{{user_email}}'  => $lead->email,
            '{{course_name}}' => $course->name,
            '{{booking_url}}' => $this->resumeUrl($lead),
        ];

        try {
            // No CC: this is a nudge about an unfinished form, not news anybody
            // needs to act on (FR-062).
            Mail::to($lead->email)
                ->send(new TemplatedMail(
                    $template->renderSubject($vars),
                    $template->renderBody($vars),
                    $template->renderText($vars),
                ));

            return true;
        } catch (\Exception $e) {
            Log::error('High ticket resume reminder failed', [
                'lead_id' => $lead->id,
                'error'   => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function courseUrl(Course $course): string
    {
        return rtrim((string) config('app.url'), '/') . '/course/' . ($course->slug ?: $course->id);
    }

    /**
     * Stage-one mail. Hardcoded rather than template-driven (FR-058): it exists
     * to carry the confirm link, and a missing template row used to fail the
     * whole application with a 422.
     */
    private function sendVerifyMail(
        HighTicketLead $lead,
        Course $course,
        Carbon $startsAt,
        int $minutes,
        Carbon $expiresAt
    ): bool {
        try {
            // No CC (FR-062): this is a "is this address real" check, and an
            // unconfirmed application is already visible in the leads list.
            Mail::to($lead->email)
                ->send(new BookingVerifyMail(
                    $lead->name,
                    $course->name,
                    $this->slots->label($startsAt),
                    $minutes,
                    url("/booking/confirm/{$lead->confirm_token}"),
                    // Carries the date: an application at 23:45 expires tomorrow,
                    // and 「今天 00:45」 would be a lie.
                    $expiresAt->timezone(ConsultationSlotService::DISPLAY_TZ)->format('n/j H:i'),
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
     * Statuses that mean the consultation is settled or already happened
     * (011 FR-065). `closed` and `cancelled` are absent on purpose — somebody
     * re-applying after either of those is the re-engagement we want.
     */
    private const BLOCKING_STATUSES = ['contacted', 'converted', 'no_response'];

    /**
     * The refusal message for a repeat application, or null to let it through.
     *
     * Matches on email OR normalised phone: the same person with a second inbox
     * used to walk straight past the `(email, course_id)` check and hold a
     * second slot.
     *
     * @param array<string, mixed> $data
     */
    /**
     * Re-run the gate on the answers this very request carries (011 FR-129).
     *
     * Trusting the `screened_at` already on the lead is not enough: screening
     * as one address and submitting as another lands on a different row and
     * walks straight past the gate.
     *
     * A payload with no answers at all is let through on purpose — somebody
     * resuming from a 「通知新時段」 mail never sees step 1, and every lead
     * predating this feature has nothing to re-score. Turning those away would
     * spend a soft filter on real bookings (D97).
     *
     * @param array<string, mixed> $data
     * @return array{success: bool, message: string}|null
     */
    private function screeningRefusal(array $data): ?array
    {
        if (!BookingScreening::answered($data) || BookingScreening::passes($data)) {
            return null;
        }

        return ['success' => false, 'message' => '此申請未通過資格審核'];
    }

    private function duplicateMessage(Course $course, array $data): ?string
    {
        $lead = $this->existingLead($course, $data);

        if (!$lead || !$this->isLive($lead)) {
            return null;
        }

        $contact = $this->contactEmailFor($lead);

        return "你已經預約過這門課的諮詢。若需要改期，或希望安排第二次面談，請直接聯絡 {$contact}。";
    }

    /**
     * Status is read before `confirmed_at` and that order matters: a `closed`
     * lead almost always carries a confirmation too (you talk first, then it
     * goes cold), so testing the timestamp first would block every re-engagement.
     */
    private function isLive(HighTicketLead $lead): bool
    {
        if (in_array($lead->status, ['closed', 'cancelled'], true)) {
            return false;
        }

        if (in_array($lead->status, self::BLOCKING_STATUSES, true)) {
            return true;
        }

        // pending: a confirmed booking is 等待面談; an unconfirmed one is still
        // mid-application, where changing your mind about the slot is normal.
        return $lead->confirmed_at !== null && $lead->cancelled_at === null;
    }

    /** @param array<string, mixed> $data */
    private function existingLead(Course $course, array $data): ?HighTicketLead
    {
        $phone = PhoneNumber::normalise($data['phone'] ?? null);

        return HighTicketLead::where('course_id', $course->id)
            ->where(function ($query) use ($data, $phone) {
                $query->where('email', $data['email']);

                if ($phone !== null) {
                    $query->orWhere('phone', $phone);
                }
            })
            ->latest('id')
            ->first();
    }

    /** Who to write to about this booking: its consultant, else support (FR-066). */
    private function contactEmailFor(HighTicketLead $lead): string
    {
        $consultant = $lead->consultant_id
            ? \App\Models\User::whereKey($lead->consultant_id)->value('email')
            : null;

        return $consultant ?: SiteSetting::supportEmail();
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

        // Carried through the wizard so the row keeps the answers even when the
        // applicant never went through step 1 in this browser session (011 US24).
        if (BookingScreening::answered($data)) {
            $answers = BookingScreening::only($data);

            $application = array_merge($application, $answers, [
                'screening_score' => BookingScreening::score($answers),
                'screened_at'     => now(),
                'declined_at'     => null,
            ]);
        }

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
            // 'declined' likewise (FR-127 / D97) — getting this far means the
            // screening let them through, so the old refusal is spent.
            'status'       => in_array($lead->status, ['closed', 'no_response', 'cancelled', 'declined'], true) ? 'pending' : $lead->status,
            'cancelled_at' => null,
        ]));

        return $lead;
    }

    /**
     * Who is copied on the one mail that still has a CC (FR-062).
     *
     * The consultant alone: they own the booking, and the support list was a
     * second copy of the same news for whoever also happens to be that person.
     *
     * The support list survives only as a fallback for an unassigned booking —
     * without it a booking on a slot nobody claimed would reach no inbox at
     * all, and "nobody was told" is the failure mode this whole module keeps
     * having to design against.
     *
     * @param array<int, string> $extraCc
     * @return array<int, string>
     */
    private function confirmationCc(HighTicketLead $lead, array $extraCc = []): array
    {
        $consultant = $lead->consultant_id
            ? \App\Models\User::whereKey($lead->consultant_id)->value('email')
            : null;

        $recipients = $consultant ? [$consultant] : $this->notifyCc();

        return array_values(array_unique(array_filter(array_merge($recipients, $extraCc))));
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
