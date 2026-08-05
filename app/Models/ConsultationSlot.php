<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One 15-minute unit of consultation availability (011 US10).
 *
 * Availability is derived, never stored (FR-029 / D33):
 *   lead_id null                 → free
 *   lead_id set, held_until > now → held for someone mid-confirmation
 *   lead_id set, held_until null  → confirmed booking
 *   lead_id set, held_until <= now → hold expired, free again
 */
class ConsultationSlot extends Model
{
    /** Every booking is a multiple of this. */
    public const UNIT_MINUTES = 15;

    protected $fillable = [
        'starts_at',
        'lead_id',
        'held_until',
        'consultant_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'held_until' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(HighTicketLead::class, 'lead_id');
    }

    /**
     * The single implementation of "can somebody book this" (FR-029).
     *
     * Expired holds are treated as free here rather than waiting for the
     * release command, so availability stays correct even if the schedule
     * stops running (FR-035 / D33).
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('lead_id')
                ->orWhere(function (Builder $q2) {
                    $q2->whereNotNull('held_until')->where('held_until', '<=', now());
                });
        });
    }

    /** Who owns this stretch of time (011 US15 / FR-060). Null = unassigned. */
    public function consultant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consultant_id');
    }

    /** Slots that have not started yet — the only ones worth offering. */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>', now());
    }

    /** Mirror of scopeAvailable for an already-loaded row. */
    public function isAvailable(): bool
    {
        if ($this->lead_id === null) {
            return true;
        }

        return $this->held_until !== null && $this->held_until->isPast();
    }
}
