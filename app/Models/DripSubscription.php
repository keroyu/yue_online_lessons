<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class DripSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'subscribed_at',
        'emails_sent',
        'status',
        'status_changed_at',
        'unsubscribe_token',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'status_changed_at' => 'datetime',
            'emails_sent' => 'integer',
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
