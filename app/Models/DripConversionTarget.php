<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DripConversionTarget extends Model
{
    protected $fillable = [
        'drip_course_id',
        'target_course_id',
    ];

    public function dripCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'drip_course_id');
    }

    public function targetCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'target_course_id');
    }
}
