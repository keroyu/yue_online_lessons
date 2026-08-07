<?php

namespace App\Services;

use App\Models\Course;
use App\Models\HighTicketLead;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * iCalendar (RFC 5545) invitations for consultation bookings (011 US14).
 *
 * Hand-rolled rather than pulled from a package (D51): one VEVENT with eight
 * properties does not justify a dependency that also ships recurrence rules and
 * a timezone database. The two parts worth care are both in here — CRLF line
 * endings and UTF-8-safe 75-octet folding.
 *
 * Everything is METHOD:REQUEST or METHOD:CANCEL, never PUBLISH: only REQUEST
 * gets the interactive "add to calendar" card in Gmail (D49). The price of that
 * is a lifecycle — every change has to be followed by a matching update, which
 * is why this service arrived together with reschedule/cancel and not before.
 */
class CalendarInviteService
{
    private const CRLF = "\r\n";

    /** RFC 5545 §3.1: lines are at most 75 octets, excluding the line break. */
    private const LINE_OCTETS = 75;

    /**
     * A live booking. `$zoomUrl` may be null — Zoom is optional (FR-039) and the
     * time is worth putting in a calendar with or without a link.
     */
    public function invite(
        HighTicketLead $lead,
        Course $course,
        CarbonInterface $startsAt,
        int $minutes,
        ?string $zoomUrl = null
    ): string {
        // Deliberately does not name a contact channel. Which one is right is
        // policy the owner sets in the editable confirmation template — and the
        // live copy currently says 請勿直接回覆此信, so an .ics telling people to
        // reply would contradict the mail it arrived attached to.
        $note = '如需改期或取消，請與我們聯繫。';

        $description = $zoomUrl ? "會議連結：{$zoomUrl}\n\n{$note}" : $note;

        $properties = [
            'STATUS:CONFIRMED',
            'TRANSP:OPAQUE',
            'DESCRIPTION:' . $this->escapeText($description),
        ];

        // No empty LOCATION: some clients render the property label regardless
        // of the value, which reads as a venue nobody filled in.
        if ($zoomUrl) {
            $properties[] = 'LOCATION:' . $this->escapeText($zoomUrl);
        }

        return $this->build($lead, $course, $startsAt, $minutes, 'REQUEST', $properties);
    }

    /**
     * Withdraw the booking. Same UID, bumped SEQUENCE — that pair is what tells
     * the client to remove the event it already holds rather than file a second
     * one (FR-047).
     */
    public function cancellation(
        HighTicketLead $lead,
        Course $course,
        CarbonInterface $startsAt,
        int $minutes
    ): string {
        return $this->build($lead, $course, $startsAt, $minutes, 'CANCEL', [
            'STATUS:CANCELLED',
        ]);
    }

    /**
     * Stable for the lifetime of the lead, and derived rather than stored: the
     * lead id already is the identity, a column would only be a second copy of
     * it that can drift (D33's principle, applied to the calendar).
     */
    public function uid(HighTicketLead $lead): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';

        return "high-ticket-lead-{$lead->id}@{$host}";
    }

    /**
     * @param array<int, string> $extraProperties already-escaped property lines
     */
    private function build(
        HighTicketLead $lead,
        Course $course,
        CarbonInterface $startsAt,
        int $minutes,
        string $method,
        array $extraProperties
    ): string {
        $start = Carbon::instance($startsAt)->utc();
        $summary = "{$lead->name} 諮詢";

        $lines = array_merge([
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//' . config('app.name') . '//High Ticket Consultation//ZH-TW',
            'CALSCALE:GREGORIAN',
            "METHOD:{$method}",
            'BEGIN:VEVENT',
            'UID:' . $this->uid($lead),
            'SEQUENCE:' . (int) $lead->calendar_sequence,
            'DTSTAMP:' . $this->stamp(now()),
            'DTSTART:' . $this->stamp($start),
            'DTEND:' . $this->stamp($start->copy()->addMinutes($minutes)),
            'SUMMARY:' . $this->escapeText($summary),
            $this->organiser(),
            $this->attendee($lead),
        ], $extraProperties, [
            'END:VEVENT',
            'END:VCALENDAR',
        ]);

        return implode(self::CRLF, array_map(fn ($line) => $this->fold($line), $lines)) . self::CRLF;
    }

    private function organiser(): string
    {
        $name = $this->escapeParam((string) config('app.name'));
        $address = (string) config('mail.from.address', 'no-reply@localhost');

        return "ORGANIZER;CN={$name}:mailto:{$address}";
    }

    /**
     * RSVP is set even though nobody reads the replies: without an ATTENDEE line
     * Outlook treats a CANCEL as unrelated to the invite it holds and leaves the
     * event sitting in the calendar.
     */
    private function attendee(HighTicketLead $lead): string
    {
        $name = $this->escapeParam((string) $lead->name);

        return "ATTENDEE;CN={$name};RSVP=TRUE;PARTSTAT=NEEDS-ACTION:mailto:{$lead->email}";
    }

    /** UTC only — the slots are stored in UTC (D32), so there is nothing to convert back to. */
    private function stamp(CarbonInterface $at): string
    {
        return Carbon::instance($at)->utc()->format('Ymd\THis\Z');
    }

    /** RFC 5545 §3.3.11. Backslash first, or the escapes we add get re-escaped. */
    private function escapeText(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);

        return str_replace(['\\', ';', ',', "\n"], ['\\\\', '\;', '\,', '\\n'], $value);
    }

    /**
     * Parameter values (CN=…) live inside a quoted-ish context where the text
     * escapes above are not interpreted; the practical fix is to drop the
     * characters that would end the parameter early.
     */
    private function escapeParam(string $value): string
    {
        return str_replace(['"', ';', ':', ',', "\r", "\n"], ' ', $value);
    }

    /**
     * Fold to 75 octets, breaking only on character boundaries (D51).
     *
     * The naive version counts bytes and splits wherever the budget runs out,
     * which lands mid-character for anything Chinese — three bytes per glyph —
     * and turns the whole property into mojibake once the client unfolds it.
     * Continuation lines get one octet less because the leading space that marks
     * them counts toward the limit.
     */
    private function fold(string $line): string
    {
        if (strlen($line) <= self::LINE_OCTETS) {
            return $line;
        }

        $out = '';
        $current = '';
        $isFirst = true;

        foreach (preg_split('//u', $line, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $char) {
            $budget = $isFirst ? self::LINE_OCTETS : self::LINE_OCTETS - 1;

            if (strlen($current) + strlen($char) > $budget) {
                $out .= ($isFirst ? '' : ' ') . $current . self::CRLF;
                $isFirst = false;
                $current = '';
            }

            $current .= $char;
        }

        return $out . ($isFirst ? '' : ' ') . $current;
    }
}
