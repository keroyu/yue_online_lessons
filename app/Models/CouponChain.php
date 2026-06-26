<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CouponChain extends Model
{
    protected $fillable = [
        'alias',
        'course_id',
        'type',
        'value',
        'code_max_uses',
        'is_active',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'value'         => 'decimal:2',
            'code_max_uses' => 'integer',
            'is_active'     => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function codes(): HasMany
    {
        return $this->hasMany(CouponCode::class, 'chain_id');
    }

    /**
     * The current redeemable code for this chain: active, not exhausted, most recently created.
     */
    public function currentCode(): ?CouponCode
    {
        return $this->codes()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->where(fn ($q) => $q->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses'))
            ->latest()
            ->first();
    }
}
