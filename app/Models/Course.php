<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'tagline',
        'description',
        'description_html',
        'price',
        'original_price',
        'thumbnail',
        'instructor_name',
        'type',
        'is_published',
        'status',
        'is_visible',
        'sale_at',
        'promo_ends_at',
        'sort_order',
        'portaly_product_id',
        'duration_minutes',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'original_price' => 'integer',
            'is_published' => 'boolean',
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
            'sale_at' => 'datetime',
            'promo_ends_at' => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class)->orderBy('sort_order');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(CourseImage::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc');
    }

    /**
     * Scope for visible courses (preorder or selling, published, and is_visible)
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereIn('status', ['preorder', 'selling'])
            ->where('is_published', true)
            ->where('is_visible', true);
    }

    /**
     * Check if the course is hidden
     */
    protected function isHidden(): Attribute
    {
        return Attribute::make(
            get: fn () => !$this->is_visible
        );
    }

    /**
     * Scope for purchasable courses (selling and published)
     */
    public function scopePurchasable(Builder $query): Builder
    {
        return $query->where('status', 'selling')
            ->where('is_published', true);
    }

    /**
     * Get formatted duration (e.g., 190 -> "3小時10分鐘")
     */
    protected function durationFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->duration_minutes) {
                    return null;
                }

                $hours = floor($this->duration_minutes / 60);
                $minutes = $this->duration_minutes % 60;

                if ($hours > 0 && $minutes > 0) {
                    return "{$hours}小時{$minutes}分鐘";
                } elseif ($hours > 0) {
                    return "{$hours}小時";
                } else {
                    return "{$minutes}分鐘";
                }
            }
        );
    }

    /**
     * Generate Portaly URL from product_id
     */
    protected function portalyUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->portaly_product_id) {
                    return null;
                }

                return "https://portaly.cc/kyontw/product/{$this->portaly_product_id}";
            }
        );
    }

    /**
     * Check if promo is currently active
     */
    protected function isPromoActive(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->original_price || !$this->promo_ends_at) {
                    return false;
                }

                return $this->promo_ends_at->isFuture();
            }
        );
    }

    /**
     * Get display price (promo price if active, otherwise original_price or price)
     */
    protected function displayPrice(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->is_promo_active) {
                    return $this->price;
                }

                return $this->original_price ?? $this->price;
            }
        );
    }

    /**
     * Scope for courses with active promotions
     */
    public function scopeHasActivePromo(Builder $query): Builder
    {
        return $query->whereNotNull('original_price')
            ->where('promo_ends_at', '>', now());
    }

    /**
     * Scope for courses visible to a specific user
     * Admin: all courses (including drafts)
     * Member/Guest: only visible courses (preorder/selling + published)
     */
    public function scopeVisibleToUser(Builder $query, ?User $user = null): Builder
    {
        if ($user && $user->isAdmin()) {
            return $query; // Admin sees all courses
        }

        return $query->visible(); // Non-admin sees only visible courses
    }

    /**
     * Get full thumbnail URL for frontend use
     */
    protected function thumbnailUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->thumbnail ? "/storage/{$this->thumbnail}" : null
        );
    }
}
