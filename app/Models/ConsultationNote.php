<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One consultation session (011 US23 / D92).
 *
 * `email` is the customer identity; `lead_id` / `user_id` / `course_id` are
 * provenance markers and may all be null — a session booked through the
 * upcoming self-service coaching flow has none of them.
 */
class ConsultationNote extends Model
{
    public const SOURCE_HIGH_TICKET = 'high_ticket_booking';

    protected $fillable = [
        'email',
        'source',
        'lead_id',
        'user_id',
        'consultant_id',
        'course_id',
        'met_at',
        'zoom_meeting_id',
        'transcript',
        'transcript_fetched_at',
        'transcript_edited_at',
        'summary',
        'summary_generated_at',
        'summary_edited_at',
    ];

    protected function casts(): array
    {
        return [
            'met_at'                => 'datetime',
            'transcript_fetched_at' => 'datetime',
            'transcript_edited_at'  => 'datetime',
            'summary_generated_at'  => 'datetime',
            'summary_edited_at'     => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Normalised on write so lookups never have to care about casing
        // (FR-115) — the same stance `email_suppressions` takes.
        static::saving(function (self $note) {
            $note->email = mb_strtolower(trim((string) $note->email));
        });
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(HighTicketLead::class, 'lead_id');
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consultant_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Every session belonging to one person.
     *
     * The only correct way to ask that question — going through `lead_id` would
     * split someone who booked twice into two customers (FR-115).
     */
    public function scopeForEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', mb_strtolower(trim($email)));
    }

    /** Nothing worth keeping — used to decide whether a cancellation deletes the row (FR-114). */
    public function isEmpty(): bool
    {
        return trim((string) $this->transcript) === '' && trim((string) $this->summary) === '';
    }

    public function transcriptIsLocked(): bool
    {
        return $this->transcript_edited_at !== null;
    }

    public function summaryIsLocked(): bool
    {
        return $this->summary_edited_at !== null;
    }
}
