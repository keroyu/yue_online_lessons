<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomepageFeaturedCourse extends Model
{
    protected $fillable = ['course_id', 'blurb', 'sort_order', 'is_visible'];

    protected function casts(): array
    {
        return [
            'course_id'  => 'integer',
            'sort_order' => 'integer',
            'is_visible' => 'boolean',
        ];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    /**
     * The front-end filter (002 FR-050). Hidden rows stay in the admin list —
     * only the sidebar query narrows, so nothing about a hidden course reaches
     * the page payload.
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
