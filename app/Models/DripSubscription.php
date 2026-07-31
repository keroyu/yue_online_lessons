<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class DripSubscription extends Model
{
    /**
     * Statuses that stop the sequence. 'completed' is deliberately absent: it is
     * set at the same moment the final email is dispatched, so that last job
     * must still run.
     */
    public const STOPS_SENDING = ['booked', 'converted', 'unsubscribed'];

    /**
     * Statuses meaning the subscriber reached the funnel goal (booked or bought).
     * They are exempt from the video free-viewing countdown and reward UI —
     * there is nothing left to promote to them.
     */
    public const FUNNEL_DONE = ['booked', 'converted'];

    protected $fillable = [
        'user_id',
        'course_id',
        'subscribed_at',
        'emails_sent',
        'status',
        'unlock_all',
        'status_changed_at',
        'unsubscribe_token',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'status_changed_at' => 'datetime',
            'emails_sent' => 'integer',
            'unlock_all' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($subscription) {
            if (empty($subscription->unsubscribe_token)) {
                $subscription->unsubscribe_token = Str::uuid()->toString();
            }
        });
    }

    public function emailEvents(): HasMany
    {
        return $this->hasMany(DripEmailEvent::class, 'subscription_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === 'active'
        );
    }
}
