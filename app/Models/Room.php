<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'building',
        'capacity',
        'type', // 'Lecture', 'Lab', 'Office'
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }
}
