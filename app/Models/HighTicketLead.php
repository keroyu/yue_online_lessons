<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected function casts(): array
    {
        return [
            'booked_at' => 'datetime',
            'last_notified_at' => 'datetime',
            'notified_count' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}
