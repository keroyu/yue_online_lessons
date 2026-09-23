<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One milestone of a course roadmap (004 US7). The roadmap is optional:
 * a course has one only when it owns at least one stage.
 */
class CourseRoadmapStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description_md',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(CourseRoadmapCheckpoint::class)->orderBy('sort_order');
    }
}
