<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CouponCode extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'type',
        'value',
        'course_id',
        'expires_at',
        'max_uses',
        'used_count',
        'is_active',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'value'      => 'decimal:2',
            'expires_at' => 'datetime',
            'max_uses'   => 'integer',
            'used_count' => 'integer',
            'is_active'  => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CouponCode $coupon) {
            $coupon->code = strtoupper($coupon->code);
        });
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Active coupons: enabled and not expired.
     * Soft-deleted rows are excluded automatically by the SoftDeletes global scope.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isExhausted(): bool
    {
        return $this->max_uses !== null && $this->used_count >= $this->max_uses;
    }

    public function isSiteWide(): bool
    {
        return $this->course_id === null;
    }
}
