<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstructorPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'instructor_id',
        'course_id',
        'semester_id',
        'submission_time',
    ];

    protected $casts = [
        'submission_time' => 'datetime',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(PreferenceTimeSlot::class);
    }
}
