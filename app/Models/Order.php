<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'buyer_name', 'buyer_email', 'buyer_phone', 'tax_id',
        'total_amount', 'coupon_code', 'original_amount', 'discount_amount',
        'referrer_user_id', 'referral_rate', 'referral_reward_points', 'referral_discount_amount',
        'currency', 'payment_gateway',
        'merchant_order_no', 'status', 'gateway_trade_no',
        'webhook_received_at',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        'referrer_domain', 'gclid', 'fbclid', 'ttclid',
    ];

    protected function casts(): array
    {
        return [
            'total_amount'           => 'decimal:2',
            'original_amount'        => 'decimal:2',
            'discount_amount'        => 'decimal:2',
            'referral_rate'          => 'integer',
            'referral_reward_points' => 'integer',
            'referral_discount_amount' => 'integer',
            'webhook_received_at'    => 'datetime',
            'status'                 => 'string',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', 'pending');
    }

    public function scopePaid(Builder $q): Builder
    {
        return $q->where('status', 'paid');
    }

    public function isPaid(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === 'paid',
        );
    }
}
