<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = ['assignment_id', 'user_id', 'parent_id', 'content', 'is_edited', 'submitted_at'];

    protected function casts(): array
    {
        return ['is_edited' => 'boolean', 'submitted_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at');
    }

    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /** Formally submitted work — the only thing the instructor ever sees (FR-035). */
    public function scopeSubmitted(Builder $query): Builder
    {
        return $query->whereNotNull('submitted_at');
    }

    public function scopeDrafts(Builder $query): Builder
    {
        return $query->whereNull('submitted_at');
    }

    public function isDraft(): bool
    {
        return $this->submitted_at === null;
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }
}
