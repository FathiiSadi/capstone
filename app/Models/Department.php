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
}
