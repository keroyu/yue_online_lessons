<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseDailyStat extends Model
{
    protected $fillable = [
        'course_id', 'date', 'channel',
        'views', 'add_to_cart', 'checkouts', 'purchases', 'revenue',
    ];

    protected function casts(): array
    {
        return [
            'date'        => 'date',
            'views'       => 'integer',
            'add_to_cart' => 'integer',
            'checkouts'   => 'integer',
            'purchases'   => 'integer',
            'revenue'     => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
