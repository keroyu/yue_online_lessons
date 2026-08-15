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
        'course_plan_id',
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
        // Only meaningful on free claims (source = 'free'); a checkout purchase
        // carries its attribution on the matching order (002 US17).
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'referrer_domain',
        'gclid',
        'fbclid',
        'ttclid',
        'first_touch',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'webhook_received_at' => 'datetime',
            'first_touch' => 'array',
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

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CoursePlan::class, 'course_plan_id');
    }

    /**
     * Lesson ids this entitlement covers, or null for the whole course.
     *
     * Null is the normal case (FR-087): every purchase made before plans
     * existed, and every path with no plan picker — checkout, gifting, member
     * import, point redemption — leaves this column alone.
     *
     * A dangling plan reference yields an empty array rather than null: when
     * the entitlement is unclear, showing nothing is the safe direction.
     */
    public function accessibleLessonIds(): ?array
    {
        if (!$this->course_plan_id) {
            return null;
        }

        return $this->plan?->lessons()->pluck('lessons.id')->all() ?? [];
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
                    'lead_conversion' => '顧問轉換',
                    default => $this->type,
                };
            }
        );
    }
}
