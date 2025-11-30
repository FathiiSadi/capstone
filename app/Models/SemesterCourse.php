<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SemesterCourse extends Pivot
{
    public $incrementing = true;

    protected $table = 'semester_courses';

    protected $fillable = [
        'semester_id',
        'course_id',
        'sections_required',
        'sections_per_instructor',
    ];

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
