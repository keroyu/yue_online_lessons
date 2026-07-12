<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostCtaClick extends Model
{
    protected $fillable = ['post_id', 'course_id', 'date', 'clicks'];

    protected function casts(): array
    {
        return [
            'date'   => 'date',
            'clicks' => 'integer',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
