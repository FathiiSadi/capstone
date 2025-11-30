<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $table = 'departments';
    protected $fillable = [
        'name',
        'code',
        'manager_id',
    ];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function manager(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function instructors(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Instructor::class, 'department_instructors');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
