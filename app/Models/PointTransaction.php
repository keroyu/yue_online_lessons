<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointTransaction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'reference_type',
        'reference_id',
        'note',
        'available_at',
        'matured_synced',
    ];

    protected function casts(): array
    {
        return [
            'amount'         => 'integer',
            'available_at'   => 'datetime',
            'created_at'     => 'datetime',
            'matured_synced' => 'boolean',
        ];
    }

    // 沿用 LessonProgress / AssignmentCompletion 的 write-once created_at 寫法
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->created_at)) {
                $model->created_at = $model->freshTimestamp();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeMatured(Builder $query): Builder
    {
        return $query->where('available_at', '<=', now());
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('available_at', '>', now());
    }
}
