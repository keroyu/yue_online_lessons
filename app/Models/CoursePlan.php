<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A purchasable tier of a high-ticket course: a named subset of its lessons.
 *
 * Plans may overlap freely — the same lesson can belong to any number of them.
 * A course with no plans is a single-tier course and behaves exactly as it did
 * before plans existed; the row count is the feature switch (D82).
 */
class CoursePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'name',
        'price',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'course_plan_lesson');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }
}
