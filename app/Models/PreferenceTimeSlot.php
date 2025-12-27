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
    ];

    protected $casts = [
        'days' => 'array',
    ];

    public function preference(): BelongsTo
    {
        return $this->belongsTo(InstructorPreference::class, 'instructor_preference_id');
    }
}
