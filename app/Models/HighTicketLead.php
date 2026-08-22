<?php

namespace App\Models;

use App\Support\BookingScreening;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HighTicketLead extends Model
{
    protected $fillable = [
        'name',
        'email',
        'course_id',
        'consultant_id',
        'status',
        'notified_count',
        'last_notified_at',
        'booked_at',
        'phone',
        'occupation',
        'bottleneck',
        'expertise',
        'social_url',
        'screen_timeline',
        'screen_budget',
        'screen_authority',
        'screen_pain',
        'screen_next_step',
        'screening_score',
        'screened_at',
        'declined_at',
        'commitments_accepted_at',
        'booking_code',
        'confirm_token',
        'confirm_expires_at',
        'confirmed_at',
        'resume_token',
        'zoom_meeting_id',
        'zoom_join_url',
        'calendar_sequence',
        'cancelled_at',
        'reminder_sent_at',
        'resume_reminder_sent_at',
    ];

    /**
     * The screening answers are stored as keys (011 D100), so every consumer
     * would otherwise need its own copy of the option labels. Resolving them
     * here keeps `BookingScreening` the only place that knows the wording —
     * shipping the whole question table to the admin page to look up five
     * strings would be the same duplication with extra steps.
     */
    protected $appends = ['screening_tier', 'screening_answers'];

    /** 8–10 高購買意願 / 5–7 值得談 / 0–4 培育名單 (011 FR-131). */
    public function getScreeningTierAttribute(): string
    {
        return BookingScreening::tier($this->screening_score);
    }

    /** @return array<int, array{title: string, answer: string}> */
    public function getScreeningAnswersAttribute(): array
    {
        if ($this->screened_at === null) {
            return [];
        }

        $out = [];

        foreach (BookingScreening::QUESTIONS as $field => $question) {
            $out[] = [
                'title'  => $question['title'],
                'answer' => $question['options'][$this->{$field}]['label'] ?? '—',
            ];
        }

        return $out;
    }

    protected function casts(): array
    {
        return [
            'booked_at' => 'datetime',
            'last_notified_at' => 'datetime',
            'notified_count' => 'integer',
            'commitments_accepted_at' => 'datetime',
            'screening_score' => 'integer',
            'screened_at' => 'datetime',
            'declined_at' => 'datetime',
            'confirm_expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'resume_reminder_sent_at' => 'datetime',
            'calendar_sequence' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * The consultant who owns this booking (011 US15 / FR-061).
     *
     * Snapshotted at confirmation rather than read through the slot: the slot
     * can be reassigned, and a cancellation hands it back to the pool (D58).
     */
    public function consultant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consultant_id');
    }

    /**
     * Every consultation this person has had — joined on email, not id (011 FR-116).
     *
     * Deliberately not `hasMany(..., 'lead_id')`: someone who books a second
     * session, or later buys 1-on-1 coaching, produces rows that belong to no
     * lead. Keying on email is what makes the admin panel show one customer's
     * whole history instead of one booking's.
     */
    public function consultationNotes(): HasMany
    {
        return $this->hasMany(ConsultationNote::class, 'email', 'email')->orderByDesc('met_at');
    }

    /** Slots this lead holds — 2 rows for a 30-minute consultation, 3 for 45. */
    public function slots(): HasMany
    {
        return $this->hasMany(ConsultationSlot::class, 'lead_id')->orderBy('starts_at');
    }

    /** The booking is only real once the visitor clicked the emailed link (FR-033). */
    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    /** A booking that exists right now — confirmed and not called off (FR-048). */
    public function isActiveBooking(): bool
    {
        return $this->confirmed_at !== null && $this->cancelled_at === null;
    }

    /** A pending confirmation that has not run out its hour yet. */
    public function isAwaitingConfirmation(): bool
    {
        return $this->confirmed_at === null
            && $this->confirm_expires_at !== null
            && $this->confirm_expires_at->isFuture();
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Leads holding a consultation unit inside [$from, $until) (011 FR-145).
     *
     * Any one of the booking's units landing in the window is enough — a
     * 30-minute consultation is 2 rows, 45 is 3, and they are consecutive.
     *
     * Half-open rather than whereBetween: the latter closes both ends, which
     * would put a slot starting exactly at midnight in both adjacent days.
     */
    public function scopeMetWithin(Builder $query, CarbonInterface $from, CarbonInterface $until): Builder
    {
        return $query->whereHas('slots', fn (Builder $q) => $q
            ->where('starts_at', '>=', $from)
            ->where('starts_at', '<', $until));
    }
}
