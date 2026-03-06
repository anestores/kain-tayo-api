<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDietaryRestriction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'dietary_restriction_id',
        'custom_name',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dietaryRestriction(): BelongsTo
    {
        return $this->belongsTo(DietaryRestriction::class);
    }
}
