<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DietaryRestriction extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'icon',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function userDietaryRestrictions(): HasMany
    {
        return $this->hasMany(UserDietaryRestriction::class);
    }
}
