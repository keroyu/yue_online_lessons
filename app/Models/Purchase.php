<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'source',
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
}
