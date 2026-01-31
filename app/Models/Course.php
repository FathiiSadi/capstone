<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Course extends Model
{
    protected $table = 'courses';


    protected $fillable = [
        'name',
        'code',
        'hours',
        'credits',
        'sections',
        'department_id',
        'office_hours',
    ];

    protected $casts = [
        'office_hours' => 'boolean',
        'sections' => 'integer',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function semesters(): BelongsToMany
    {
        return $this->belongsToMany(Semester::class, 'semester_courses')
            ->withPivot(['sections_required', 'sections_per_instructor'])
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_courses');
    }

    public function sections(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Section::class);
    }
}
