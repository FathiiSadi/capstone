<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'preferences_open_at',
        'preferences_closed_at',
        'status',
    ];

    protected $casts = [
        'preferences_open_at' => 'datetime',
        'preferences_closed_at' => 'datetime',
    ];

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'semester_courses')
            ->withPivot(['sections_required', 'sections_per_instructor'])
            ->withTimestamps();
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }
}
