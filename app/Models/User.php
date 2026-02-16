<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
     * Get the course completion progress percentage for a specific course.
     */
    public function getCourseProgress(Course $course): int
    {
        $totalLessons = $course->lessons()->count();
        if ($totalLessons === 0) {
            return 0;
        }

        $completedLessons = $this->lessonProgress()
            ->whereIn('lesson_id', $course->lessons()->pluck('id'))
            ->count();

        return (int) round($completedLessons / $totalLessons * 100);
    }
}
