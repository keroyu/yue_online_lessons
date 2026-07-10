<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Broadcast extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'subject',
        'status',
        'scheduled_at',
        'recipients_count',
        'sent_count',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'recipients_count' => 'integer',
            'sent_count' => 'integer',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function emailEvents(): HasMany
    {
        return $this->hasMany(NewsletterEmailEvent::class);
    }

    /**
     * Distinct opened count for this broadcast.
     */
    public function openedCount(): int
    {
        return $this->emailEvents()->where('event_type', 'opened')->count();
    }
}
