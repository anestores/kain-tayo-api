<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HouseholdMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'gender',
        'age',
        'activity_level',
        'dietary_restrictions',
        'health_conditions',
        'is_pregnant',
    ];

    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'dietary_restrictions' => 'array',
            'health_conditions' => 'array',
            'is_pregnant' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
