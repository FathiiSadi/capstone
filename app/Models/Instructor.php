<?php

namespace App\Models;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

}
