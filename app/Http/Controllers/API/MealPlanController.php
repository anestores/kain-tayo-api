<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\MealPlanItem;
use App\Models\Recipe;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Services\AIMealPlanService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MealPlanController extends Controller
{
    public function generate(Request $request)
    {
        try {
            $user = $request->user();
            $profile = $user->profile;

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile not found. Please set up your profile first.',
                ], 404);
            }

            $prompt = $request->input('prompt');
            $aiPlan = null;

            // If prompt provided, try AI generation
            if ($prompt) {
                $aiService = new AIMealPlanService();
                $aiPlan = $aiService->generatePlan($user, $prompt);
            }

            DB::beginTransaction();

            // Deactivate any existing active plans
            MealPlan::where('user_id', $user->id)
                ->where('status', 'active')
                ->update(['status' => 'inactive']);

            $mealPlan = MealPlan::create([
                'user_id' => $user->id,
                'week_start_date' => Carbon::now()->startOfWeek(),
                'status' => 'active',
            ]);

            $totalCost = 0;

            $mealTypes = ['almusal', 'tanghalian', 'merienda', 'hapunan'];
            $userRestrictionIds = $user->dietaryRestrictions()->pluck('dietary_restrictions.id')->toArray();
            $userEquipmentIds = $user->equipment()->pluck('equipment.id')->toArray();

            // Build a map of filled slots from AI plan
            $filledSlots = [];
            if ($aiPlan) {
                foreach ($aiPlan as $item) {
                    $recipe = Recipe::find($item['recipe_id']);
                    if ($recipe) {
                        MealPlanItem::create([
                            'meal_plan_id' => $mealPlan->id,
                            'recipe_id' => $recipe->id,
                            'day_number' => $item['day'],
                            'meal_type' => $item['meal_type'],
                        ]);
                        $recipeCost = $recipe->ingredients()->sum('recipe_ingredients.estimated_cost');
                        $totalCost += $recipeCost;
                        $filledSlots["{$item['day']}-{$item['meal_type']}"] = true;
                    }
                }
            }

            // Fill any missing slots with random recipes
            $allRecipeIds = $mealPlan->items()->pluck('recipe_id')->toArray();
            foreach (range(1, 7) as $day) {
                foreach ($mealTypes as $mealType) {
                    if (isset($filledSlots["{$day}-{$mealType}"])) continue;

                    $recipe = $this->findSuitableRecipe($mealType, $userRestrictionIds, $userEquipmentIds, $allRecipeIds);

                    if (!$recipe) {
                        $recipe = Recipe::where('meal_type', $mealType)
                            ->whereNotIn('id', $allRecipeIds)
                            ->inRandomOrder()
                            ->first();
                    }

                    if (!$recipe) {
                        $recipe = Recipe::where('meal_type', $mealType)
                            ->inRandomOrder()
                            ->first();
                    }

                    if ($recipe) {
                        MealPlanItem::create([
                            'meal_plan_id' => $mealPlan->id,
                            'recipe_id' => $recipe->id,
                            'day_number' => $day,
                            'meal_type' => $mealType,
                        ]);
                        $allRecipeIds[] = $recipe->id;
                        $recipeCost = $recipe->ingredients()->sum('recipe_ingredients.estimated_cost');
                        $totalCost += $recipeCost;
                    }
                }
            }

            $mealPlan->update(['total_cost' => $totalCost]);

            // Generate shopping list
            $this->generateShoppingList($mealPlan, $user->id);

            DB::commit();

            $mealPlan->load(['items.recipe.ingredients', 'items.recipe.equipment', 'shoppingList.items.ingredient']);

            $groupedItems = $mealPlan->items->groupBy('day_number');

            return response()->json([
                'success' => true,
                'message' => $aiPlan ? 'AI meal plan generated successfully' : 'Meal plan generated successfully',
                'data' => [
                    'meal_plan' => $mealPlan,
                    'days' => $groupedItems,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate meal plan',
            ], 500);
        }
    }

    public function active(Request $request)
    {
        try {
            $user = $request->user();
            $mealPlan = MealPlan::where('user_id', $user->id)
                ->where('status', 'active')
                ->with(['items.recipe.ingredients', 'items.recipe.equipment'])
                ->latest()
                ->first();

            if (!$mealPlan) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                ]);
            }

            $groupedItems = $mealPlan->items->groupBy('day_number');

            return response()->json([
                'success' => true,
                'data' => [
                    'meal_plan' => $mealPlan,
                    'days' => $groupedItems,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve active meal plan',
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $mealPlan = MealPlan::with(['items.recipe.ingredients', 'items.recipe.equipment', 'items.recipe.dietaryFlags'])
                ->findOrFail($id);

            $groupedItems = $mealPlan->items->groupBy('day_number');

            return response()->json([
                'success' => true,
                'data' => [
                    'meal_plan' => $mealPlan,
                    'days' => $groupedItems,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Meal plan not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve meal plan',
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();

            // Delete all meal plans for this user
            MealPlan::where('user_id', $user->id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'All meal plans deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete meal plans',
            ], 500);
        }
    }

    public function swap(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'day_number' => 'required|integer|min:1|max:7',
            'meal_type' => 'required|in:almusal,tanghalian,merienda,hapunan',
            'recipe_id' => 'required|exists:recipes,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = $request->user();
            $mealPlan = MealPlan::where('user_id', $user->id)->findOrFail($id);

            $item = MealPlanItem::where('meal_plan_id', $mealPlan->id)
                ->where('day_number', $request->day_number)
                ->where('meal_type', $request->meal_type)
                ->first();

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meal plan item not found',
                ], 404);
            }

            $item->update(['recipe_id' => $request->recipe_id]);

            // Recalculate total cost
            $totalCost = 0;
            $mealPlan->load('items.recipe.ingredients');
            foreach ($mealPlan->items as $planItem) {
                $recipeCost = $planItem->recipe->ingredients()->sum('recipe_ingredients.estimated_cost');
                $totalCost += $recipeCost;
            }
            $mealPlan->update(['total_cost' => $totalCost]);

            // Regenerate shopping list
            $this->generateShoppingList($mealPlan, $user->id);

            DB::commit();

            $mealPlan->load(['items.recipe.ingredients', 'items.recipe.equipment', 'shoppingList.items.ingredient']);
            $groupedItems = $mealPlan->items->groupBy('day_number');

            return response()->json([
                'success' => true,
                'message' => 'Meal swapped successfully',
                'data' => [
                    'meal_plan' => $mealPlan,
                    'days' => $groupedItems,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Meal plan not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to swap meal',
            ], 500);
        }
    }

    public function alternatives(Request $request, $id)
    {
        $validator = Validator::make($request->query(), [
            'day_number' => 'required|integer|min:1|max:7',
            'meal_type' => 'required|in:almusal,tanghalian,merienda,hapunan',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $request->user();
            $mealPlan = MealPlan::where('user_id', $user->id)->findOrFail($id);

            $existingRecipeIds = $mealPlan->items()->pluck('recipe_id')->toArray();

            $userRestrictionIds = $user->dietaryRestrictions()->pluck('dietary_restrictions.id')->toArray();
            $userEquipmentIds = $user->equipment()->pluck('equipment.id')->toArray();

            $query = Recipe::where('meal_type', $request->query('meal_type'))
                ->whereNotIn('id', $existingRecipeIds);

            // Exclude recipes that violate user's dietary restrictions
            if (!empty($userRestrictionIds)) {
                $query->whereDoesntHave('dietaryFlags', function ($q) use ($userRestrictionIds) {
                    $q->whereIn('dietary_restrictions.id', $userRestrictionIds);
                });
            }

            // Only include recipes whose equipment the user has
            if (!empty($userEquipmentIds)) {
                $query->whereDoesntHave('equipment', function ($q) use ($userEquipmentIds) {
                    $q->whereNotIn('equipment.id', $userEquipmentIds);
                });
            }

            $alternatives = $query->with(['ingredients', 'equipment'])->limit(10)->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'alternatives' => $alternatives,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Meal plan not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve alternatives',
            ], 500);
        }
    }

    public function addMeal(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'day_number' => 'required|integer|min:1|max:7',
            'meal_type' => 'required|in:almusal,tanghalian,merienda,hapunan',
            'recipe_id' => 'required|exists:recipes,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = $request->user();
            $mealPlan = MealPlan::where('user_id', $user->id)->findOrFail($id);

            // Check if slot already has a meal — if so, update it (swap)
            $existing = MealPlanItem::where('meal_plan_id', $mealPlan->id)
                ->where('day_number', $request->day_number)
                ->where('meal_type', $request->meal_type)
                ->first();

            if ($existing) {
                $existing->update(['recipe_id' => $request->recipe_id]);
            } else {
                MealPlanItem::create([
                    'meal_plan_id' => $mealPlan->id,
                    'recipe_id' => $request->recipe_id,
                    'day_number' => $request->day_number,
                    'meal_type' => $request->meal_type,
                ]);
            }

            // Recalculate total cost
            $totalCost = 0;
            $mealPlan->load('items.recipe.ingredients');
            foreach ($mealPlan->items as $planItem) {
                $recipeCost = $planItem->recipe->ingredients()->sum('recipe_ingredients.estimated_cost');
                $totalCost += $recipeCost;
            }
            $mealPlan->update(['total_cost' => $totalCost]);

            // Regenerate shopping list
            $this->generateShoppingList($mealPlan, $user->id);

            DB::commit();

            $mealPlan->load(['items.recipe.ingredients', 'items.recipe.equipment', 'shoppingList.items.ingredient']);
            $groupedItems = $mealPlan->items->groupBy('day_number');

            return response()->json([
                'success' => true,
                'message' => 'Meal added successfully',
                'data' => [
                    'meal_plan' => $mealPlan,
                    'days' => $groupedItems,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Meal plan not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add meal',
            ], 500);
        }
    }

    private function findSuitableRecipe(string $mealType, array $restrictionIds, array $equipmentIds, array $excludeIds): ?Recipe
    {
        $query = Recipe::where('meal_type', $mealType)
            ->whereNotIn('id', $excludeIds);

        // Exclude recipes that violate user's dietary restrictions
        if (!empty($restrictionIds)) {
            $query->whereDoesntHave('dietaryFlags', function ($q) use ($restrictionIds) {
                $q->whereIn('dietary_restrictions.id', $restrictionIds);
            });
        }

        // Only include recipes whose required equipment the user has
        if (!empty($equipmentIds)) {
            $query->whereDoesntHave('equipment', function ($q) use ($equipmentIds) {
                $q->whereNotIn('equipment.id', $equipmentIds);
            });
        }

        return $query->inRandomOrder()->first();
    }

    private function generateShoppingList(MealPlan $mealPlan, int $userId): void
    {
        // Delete existing shopping list
        ShoppingList::where('meal_plan_id', $mealPlan->id)->delete();

        $shoppingList = ShoppingList::create([
            'meal_plan_id' => $mealPlan->id,
            'user_id' => $userId,
        ]);

        // Aggregate ingredients from all recipes in the meal plan
        $aggregatedIngredients = DB::table('meal_plan_items')
            ->join('recipe_ingredients', 'meal_plan_items.recipe_id', '=', 'recipe_ingredients.recipe_id')
            ->join('ingredients', 'recipe_ingredients.ingredient_id', '=', 'ingredients.id')
            ->where('meal_plan_items.meal_plan_id', $mealPlan->id)
            ->select(
                'ingredients.id as ingredient_id',
                DB::raw('SUM(recipe_ingredients.quantity) as total_quantity'),
                'recipe_ingredients.unit',
                DB::raw('SUM(recipe_ingredients.estimated_cost) as total_price')
            )
            ->groupBy('ingredients.id', 'recipe_ingredients.unit')
            ->get();

        foreach ($aggregatedIngredients as $item) {
            ShoppingListItem::create([
                'shopping_list_id' => $shoppingList->id,
                'ingredient_id' => $item->ingredient_id,
                'quantity' => $item->total_quantity,
                'unit' => $item->unit,
                'estimated_price' => $item->total_price,
            ]);
        }
    }
}
