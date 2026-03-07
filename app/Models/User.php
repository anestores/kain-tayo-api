<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function userDietaryRestrictions(): HasMany
    {
        return $this->hasMany(UserDietaryRestriction::class);
    }

    public function userEquipment(): HasMany
    {
        return $this->hasMany(UserEquipment::class);
    }

    public function householdMembers(): HasMany
    {
        return $this->hasMany(HouseholdMember::class);
    }

    public function mealPlans(): HasMany
    {
        return $this->hasMany(MealPlan::class);
    }

    public function recipeHistories(): HasMany
    {
        return $this->hasMany(RecipeHistory::class);
    }

    public function favoriteMarkets(): BelongsToMany
    {
        return $this->belongsToMany(Market::class, 'user_favorite_markets');
    }

    /**
     * Get the actual dietary restriction items through the pivot.
     */
    public function dietaryRestrictions(): BelongsToMany
    {
        return $this->belongsToMany(DietaryRestriction::class, 'user_dietary_restrictions')
            ->withPivot('custom_name');
    }

    /**
     * Get the actual equipment items through the pivot.
     */
    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class, 'user_equipment');
    }
}
