<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomepageFeaturedCourse extends Model
{
    protected $fillable = ['course_id', 'blurb', 'sort_order'];

    protected function casts(): array
    {
        return [
            'course_id'  => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
