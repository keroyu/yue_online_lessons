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
            'calendar_sequence' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
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
