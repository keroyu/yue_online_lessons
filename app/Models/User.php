<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\LessonProgress;
use App\Models\Course;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'password',
        'nickname',
        'real_name',
        'phone',
        'birth_date',
        'role',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'birth_date' => 'date',
            'password' => 'hashed',
        ];
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isEditor(): bool
    {
        return $this->role === 'editor';
    }

    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    public function scopeMembers(Builder $query): Builder
    {
        return $query->where('role', 'member');
    }

    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function dripSubscriptions(): HasMany
    {
        return $this->hasMany(DripSubscription::class);
    }

    public function activeDripSubscriptions(): HasMany
    {
        return $this->dripSubscriptions()->where('status', 'active');
    }

    /**
     * Get course progress summary for a specific course.
     */
    public function getCourseProgressSummary(Course $course, ?array $completedLessonIds = null): array
    {
        $lessonIds = $course->relationLoaded('lessons')
            ? $course->lessons->pluck('id')->all()
            : $course->lessons()->pluck('id')->all();

        $totalLessons = count($lessonIds);

        if ($totalLessons === 0) {
            return [
                'completed_lessons' => 0,
                'total_lessons' => 0,
                'progress_percent' => 0,
            ];
        }

        if ($completedLessonIds !== null) {
            $completedLessons = 0;
            foreach ($lessonIds as $lessonId) {
                if (isset($completedLessonIds[$lessonId])) {
                    $completedLessons++;
                }
            }
        } else {
            $completedLessons = $this->lessonProgress()
                ->whereIn('lesson_id', $lessonIds)
                ->count();
        }

        $progressPercent = (int) round($completedLessons / $totalLessons * 100);

        return [
            'completed_lessons' => $completedLessons,
            'total_lessons' => $totalLessons,
            'progress_percent' => $progressPercent,
        ];
    }

    /**
     * Get the course completion progress percentage for a specific course.
     */
    public function getCourseProgress(Course $course): int
    {
        return $this->getCourseProgressSummary($course)['progress_percent'];
    }
}
