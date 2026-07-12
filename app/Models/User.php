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
        'is_sales_consultant',
        'points',
        'referral_code',
        'referral_activated_at',
        'last_login_at',
        'last_login_ip',
        'newsletter_status',
        'newsletter_subscribed_at',
        'newsletter_unsubscribe_token',
        'newsletter_last_opened_at',
        'newsletter_status_changed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_sales_consultant' => 'boolean',
            'last_login_at' => 'datetime',
            'referral_activated_at' => 'datetime',
            'birth_date' => 'date',
            'password' => 'hashed',
            'newsletter_subscribed_at' => 'datetime',
            'newsletter_last_opened_at' => 'datetime',
            'newsletter_status_changed_at' => 'datetime',
        ];
    }

    // 建帳號時自動產生永久推薦碼（比照 DripSubscription::unsubscribe_token 慣例）
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->referral_code)) {
                $user->referral_code = static::generateReferralCode();
            }
        });
    }

    // 8 碼大寫英數，排除易混字元（0/O/1/I/L）；碰撞重試
    public static function generateReferralCode(): string
    {
        $charset = str_split('23456789ABCDEFGHJKMNPQRSTUVWXYZ');
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $charset[array_rand($charset)];
            }
        } while (static::where('referral_code', $code)->exists());

        return $code;
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class)->orderByDesc('created_at');
    }

    public function isReferralActive(): bool
    {
        return $this->referral_activated_at !== null;
    }

    // 未成熟積分（尚未可用的回饋）
    public function pendingPoints(): int
    {
        return (int) $this->pointTransactions()
            ->where('available_at', '>', now())
            ->sum('amount');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isEditor(): bool
    {
        return $this->role === 'editor';
    }

    public function isSalesConsultant(): bool
    {
        return (bool) $this->is_sales_consultant;
    }

    // Staff panel (coupons / high-ticket leads) admits admins and sales consultants.
    public function canAccessSalesPanel(): bool
    {
        return $this->isAdmin() || $this->isSalesConsultant();
    }

    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    /**
     * Users that can be managed through the admin member panel.
     * Includes real members and the admin owner account (who is also a learner).
     */
    public function isManageableMember(): bool
    {
        return in_array($this->role, ['member', 'admin'], true);
    }

    public function scopeMembers(Builder $query): Builder
    {
        return $query->whereIn('role', ['member', 'admin']);
    }

    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function assignmentCompletions(): HasMany
    {
        return $this->hasMany(AssignmentCompletion::class);
    }

    public function homeworkNotifications(): HasMany
    {
        return $this->hasMany(HomeworkNotification::class);
    }

    public function dripSubscriptions(): HasMany
    {
        return $this->hasMany(DripSubscription::class);
    }

    public function newsletterEmailEvents(): HasMany
    {
        return $this->hasMany(NewsletterEmailEvent::class);
    }

    /**
     * Active newsletter recipients (excludes unsubscribed / dormant / none).
     */
    public function scopeNewsletterSubscribed(Builder $query): Builder
    {
        return $query->where('newsletter_status', 'subscribed');
    }

    public function isNewsletterSubscribed(): bool
    {
        return $this->newsletter_status === 'subscribed';
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
