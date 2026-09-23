<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A single self-check item inside a roadmap stage (004 US7).
 *
 * This id is the anchor every learner completion row points at (004 FR-020):
 * rebuilding checkpoints instead of updating them wipes everyone's progress.
 */
class CourseRoadmapCheckpoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_roadmap_stage_id',
        'label',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(CourseRoadmapStage::class, 'course_roadmap_stage_id');
    }

    /**
     * Learner completions (003 US11 owns the table and the model).
     */
    public function completions(): HasMany
    {
        return $this->hasMany(RoadmapCheckpointCompletion::class);
    }
}
