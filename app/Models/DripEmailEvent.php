<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DripEmailEvent extends Model
{
    // Events are immutable — no updated_at column
    const UPDATED_AT = null;

    protected $fillable = [
        'subscription_id',
        'lesson_id',
        'event_type',
        'target_url',
        'ip',
        'user_agent',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(DripSubscription::class, 'subscription_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
