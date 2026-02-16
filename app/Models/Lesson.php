<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'chapter_id',
        'title',
        'video_platform',
        'video_id',
        'video_url',
        'html_content',
        'promo_delay_minutes',
        'promo_html',
        'duration_seconds',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'sort_order' => 'integer',
            'promo_delay_minutes' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    /**
     * Get formatted duration (e.g., 230 -> "3:50")
     */
    protected function durationFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                $minutes = floor($this->duration_seconds / 60);
                $seconds = $this->duration_seconds % 60;
                return sprintf('%d:%02d', $minutes, $seconds);
            }
        );
    }

    /**
     * Get embed URL based on platform
     */
    protected function embedUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->video_id || !$this->video_platform) {
                    return null;
                }

                return match ($this->video_platform) {
                    'vimeo' => "https://player.vimeo.com/video/{$this->video_id}",
                    'youtube' => "https://www.youtube.com/embed/{$this->video_id}",
                    default => null,
                };
            }
        );
    }

    /**
     * Check if lesson has video
     */
    protected function hasVideo(): Attribute
    {
        return Attribute::make(
            get: fn () => !empty($this->video_id)
        );
    }

    /**
     * Check if lesson has a promo block configured
     */
    protected function hasPromoBlock(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->promo_delay_minutes !== null && !empty($this->promo_html)
        );
    }

    /**
     * Check if promo block should show immediately
     */
    protected function isPromoImmediate(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->promo_delay_minutes === 0
        );
    }
}
