<?php

namespace App\Models;

use App\Filament\Resources\Instructors\Schemas\InstructorForm;
use App\InstructorPosition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Instructor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'position',
        'min_credits',
    ];

    protected $casts = [
        'position' => InstructorPosition::class
    ];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function departments(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_instructors');
    }

    public function preferences(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InstructorPreference::class);
    }

    public function sections(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Section::class);
    }

}
