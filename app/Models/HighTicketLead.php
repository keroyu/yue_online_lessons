<?php

namespace App\Models;

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
    ];

    protected function casts(): array
    {
        return [
            'booked_at' => 'datetime',
            'last_notified_at' => 'datetime',
            'notified_count' => 'integer',
            'commitments_accepted_at' => 'datetime',
            'confirm_expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
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
}
