<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MealPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'week_start_date',
        'total_cost',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'week_start_date' => 'date',
            'total_cost' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MealPlanItem::class);
    }

    public function shoppingList(): HasOne
    {
        return $this->hasOne(ShoppingList::class);
    }
}
