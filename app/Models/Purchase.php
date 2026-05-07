<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\Order;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'portaly_order_id',
        'payuni_trade_no',
        'buyer_email',
        'amount',
        'currency',
        'coupon_code',
        'discount_amount',
        'status',
        'source',
        'type',
        'webhook_received_at',
        'order_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'webhook_received_at' => 'datetime',
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

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scope for records whose payment status is paid.
     */
    public function scopePaidStatus(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope for records whose payment status is refunded.
     */
    public function scopeRefundedStatus(Builder $query): Builder
    {
        return $query->where('status', 'refunded');
    }

    /**
     * Scope for purchases created through the normal checkout flow.
     */
    public function scopePurchaseType(Builder $query): Builder
    {
        return $query->where('type', 'paid');
    }

    /**
     * Scope for system-assigned purchases (admin auto-ownership).
     */
    public function scopeSystemAssignedType(Builder $query): Builder
    {
        return $query->where('type', 'system_assigned');
    }

    /**
     * Scope for gift purchases.
     */
    public function scopeGiftType(Builder $query): Builder
    {
        return $query->where('type', 'gift');
    }

    /**
     * Scope for sales reports (only paid purchases)
     */
    public function scopeForSalesReport(Builder $query): Builder
    {
        return $query->purchaseType()->paidStatus();
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
