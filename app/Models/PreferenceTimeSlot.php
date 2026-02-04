<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreferenceTimeSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'instructor_preference_id',
        'days',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'days' => 'array',
    ];

    public function getTimeRangeAttribute(): ?string
    {
        if (!$this->start_time) {
            return null;
        }

        $start = substr($this->start_time, 0, 5);
        $end = $this->end_time ? substr($this->end_time, 0, 5) : null;

        return $end ? "{$start} - {$end}" : $start;
    }

    public function preference(): BelongsTo
    {
        return $this->belongsTo(InstructorPreference::class, 'instructor_preference_id');
    }
}
