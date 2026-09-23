<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A learner ticking one roadmap checkpoint (003 US11).
 *
 * Unlike lesson_progress (003 FR-026) this is deletable by the member: it is a
 * self-assessment, not an achievement ledger — no points, no completion rate.
 */
class RoadmapCheckpointCompletion extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_roadmap_checkpoint_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(CourseRoadmapCheckpoint::class, 'course_roadmap_checkpoint_id');
    }
}
