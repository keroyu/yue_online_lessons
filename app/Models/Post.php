<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slug',
        'title',
        'body_md',
        'excerpt',
        'cover_image_path',
        'og_image_path',
        'seo_title',
        'meta_description',
        'status',
        'published_at',
        'is_featured',
        'view_count',
        'related_course_id',
        'author_id',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
            'view_count' => 'integer',
        ];
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(PostImage::class)->orderBy('sort_order')->orderByDesc('id');
    }

    public function broadcasts(): HasMany
    {
        return $this->hasMany(Broadcast::class);
    }

    public function relatedCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'related_course_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Published posts visible to the public (status published and past its publish time).
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(function (Builder $q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    protected function coverUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->cover_image_path ? Storage::url($this->cover_image_path) : null
        );
    }

    protected function ogUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->og_image_path
                ? Storage::url($this->og_image_path)
                : $this->cover_url
        );
    }
}
