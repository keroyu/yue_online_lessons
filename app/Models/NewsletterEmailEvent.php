<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsletterEmailEvent extends Model
{
    // Events are immutable — no updated_at column
    const UPDATED_AT = null;

    protected $fillable = [
        'broadcast_id',
        'user_id',
        'event_type',
        'ip',
        'user_agent',
    ];

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(Broadcast::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
