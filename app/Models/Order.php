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
        'user_id', 'buyer_name', 'buyer_email', 'buyer_phone',
        'total_amount', 'currency', 'payment_gateway',
        'merchant_order_no', 'status', 'gateway_trade_no',
        'webhook_received_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount'        => 'decimal:2',
            'webhook_received_at' => 'datetime',
            'status'              => 'string',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
