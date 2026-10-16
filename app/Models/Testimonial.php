<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A client's testimonial after a consultation (011 US40).
 *
 * Course and consultation time are snapshots taken from the lead at
 * submission (D158), so the sales page never needs the lead row.
 */
class Testimonial extends Model
{
    public const STATUSES = ['pending', 'published', 'hidden'];

    /** The only place the public wording lives (011 FR-246 / D166). */
    public const IDENTITY_LABELS = [
        'consultation' => '諮詢客戶',
        'student'      => '課程學員',
    ];

    protected $fillable = [
        'lead_id',
        'user_id',
        'course_id',
        'nickname',
        'title',
        'content',
        'consulted_at',
        'identity',
        'status',
        'reward_points',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'consulted_at'  => 'datetime',
            'published_at'  => 'datetime',
            'reward_points' => 'integer',
        ];
    }

    public function getIdentityLabelAttribute(): string
    {
        return self::IDENTITY_LABELS[$this->identity] ?? self::IDENTITY_LABELS['consultation'];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(HighTicketLead::class, 'lead_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}
