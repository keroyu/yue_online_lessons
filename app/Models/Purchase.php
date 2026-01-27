<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'portaly_order_id',
        'buyer_email',
        'amount',
        'currency',
        'coupon_code',
        'discount_amount',
        'status',
        'type',
        'webhook_received_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Scope for paid purchases (normal purchases via Portaly)
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('type', 'paid');
    }

    /**
     * Scope for system-assigned purchases (admin auto-ownership)
     */
    public function scopeSystemAssigned(Builder $query): Builder
    {
        return $query->where('type', 'system_assigned');
    }

    /**
     * Scope for gift purchases
     */
    public function scopeGift(Builder $query): Builder
    {
        return $query->where('type', 'gift');
    }

    /**
     * Scope for sales reports (only paid purchases)
     */
    public function scopeForSalesReport(Builder $query): Builder
    {
        return $query->where('type', 'paid');
    }

    /**
     * Check if this is a system-assigned purchase
     */
    protected function isSystemAssigned(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->type === 'system_assigned'
        );
    }

    /**
     * Check if this is a gift purchase
     */
    protected function isGift(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->type === 'gift'
        );
    }

    /**
     * Get display type label
     */
    protected function typeLabel(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match ($this->type) {
                    'paid' => '已付款',
                    'system_assigned' => '系統指派',
                    'gift' => '贈送',
                    default => $this->type,
                };
            }
        );
    }
}
