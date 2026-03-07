<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'meal_type',
        'difficulty',
        'cook_time_minutes',
        'servings',
        'calories',
        'protein',
        'iron',
        'vitamin_c',
        'instructions',
        'image_path',
        'ai_generated',
    ];

    protected function casts(): array
    {
        return [
            'instructions' => 'array',
            'cook_time_minutes' => 'integer',
            'servings' => 'integer',
            'calories' => 'decimal:2',
            'protein' => 'decimal:2',
            'iron' => 'decimal:2',
            'vitamin_c' => 'decimal:2',
            'ai_generated' => 'boolean',
        ];
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredients')
            ->withPivot('quantity', 'unit', 'estimated_cost');
    }

    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class, 'recipe_equipment');
    }

    /**
     * Dietary restrictions that this recipe violates.
     */
    public function dietaryFlags(): BelongsToMany
    {
        return $this->belongsToMany(DietaryRestriction::class, 'recipe_dietary_flags');
    }
}
