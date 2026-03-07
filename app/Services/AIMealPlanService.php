<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AIMealPlanService
{
    private string $model;
    private string $apiKey;

    public function __construct()
    {
        $this->model = config('services.openai.model', 'gpt-4o-mini');
        $this->apiKey = config('services.openai.api_key');
    }

    /**
     * Generate a 7-day meal plan by calling OpenAI once per day (7 calls of 4 meals each).
     */
    public function generatePlan(User $user, string $prompt): ?array
    {
        try {
            $profile = $user->profile;
            $members = $user->householdMembers()->get();
            $restrictions = $user->dietaryRestrictions()->pluck('name')->toArray();
            $equipment = $user->equipment()->pluck('name')->toArray();

            $householdDesc = $members->map(function ($m) {
                $desc = "{$m->name}: {$m->gender}, {$m->age} yrs, {$m->activity_level}";
                if ($m->is_pregnant) $desc .= ', pregnant';
                if (!empty($m->dietary_restrictions)) $desc .= ', restrictions: ' . implode(', ', $m->dietary_restrictions);
                if (!empty($m->health_conditions)) $desc .= ', conditions: ' . implode(', ', $m->health_conditions);
                return $desc;
            })->implode("\n");

            $context = "HOUSEHOLD:\n{$householdDesc}\n"
                . "Budget: PHP " . number_format($profile->budget ?? 500, 0) . "/week\n"
                . "Equipment: " . (empty($equipment) ? 'basic kitchen' : implode(', ', $equipment)) . "\n"
                . "Dietary restrictions: " . (empty($restrictions) ? 'none' : implode(', ', $restrictions)) . "\n"
                . "USER REQUEST: {$prompt}";

            Log::info('AI Meal Plan: Starting batch generation', [
                'user_id' => $user->id,
                'model' => $this->model,
                'household_members' => $members->count(),
                'budget' => $profile->budget ?? 500,
                'prompt' => $prompt,
            ]);

            $allResults = [];
            $usedRecipes = [];

            for ($day = 1; $day <= 7; $day++) {
                Log::info("AI Meal Plan: Generating day {$day}/7");

                $dayResult = $this->generateDay($day, $context, $usedRecipes);

                if ($dayResult) {
                    foreach ($dayResult as $item) {
                        $allResults[] = $item;
                        $usedRecipes[] = $item['recipe_name'] ?? '';
                    }
                    Log::info("AI Meal Plan: Day {$day} complete", ['meals' => count($dayResult)]);
                } else {
                    Log::warning("AI Meal Plan: Day {$day} failed, will be filled with random recipes");
                }
            }

            Log::info('AI Meal Plan: Batch generation complete', ['total_meals' => count($allResults)]);

            return !empty($allResults) ? $allResults : null;
        } catch (\Exception $e) {
            Log::error('AI meal plan generation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return null;
        }
    }

    /**
     * Generate 4 meals for a single day via OpenAI.
     */
    private function generateDay(int $day, string $context, array $usedRecipes): ?array
    {
        try {
            $avoidList = !empty($usedRecipes) ? "\nAVOID these dishes (already used): " . implode(', ', array_unique($usedRecipes)) : '';

            $systemPrompt = <<<SYSTEM
Filipino meal planner. Create 4 meals for Day {$day}: almusal, tanghalian, merienda, hapunan. Real Filipino dishes only.

ENUMS: meal_type: almusal|tanghalian|merienda|hapunan. difficulty: madali|katamtaman|mahirap. ingredient category: karne|isda|gulay|prutas|bigas_at_butil|gatas_at_itlog|pampalasa|iba_pa|karne_isda|bigas_butil.

RULES: Realistic PH market prices in pesos. Respect dietary restrictions & budget. Keep instructions short (2-4 steps). Keep ingredients to 3-6 per recipe. Merienda should be simple/light.{$avoidList}

Return ONLY JSON:
{"meals":[{"meal_type":"almusal","recipe":{"name":"Champorado","description":"Sweet chocolate rice porridge","meal_type":"almusal","difficulty":"madali","cook_time_minutes":25,"servings":4,"calories":280,"protein":6.5,"iron":1.8,"vitamin_c":0.5,"instructions":["Pakuluan ang bigas.","Idagdag cocoa at asukal."],"ingredients":[{"name":"Bigas malagkit","category":"bigas_at_butil","quantity":200,"unit":"g","estimated_cost":25}]}}]}

Exactly 4 meals. Every meal needs full recipe object.
SYSTEM;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $context],
                ],
                'temperature' => 0.7,
                'max_tokens' => 4000,
                'response_format' => ['type' => 'json_object'],
            ]);

            if (!$response->successful()) {
                Log::error("AI Meal Plan: OpenAI error for day {$day}", ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            $usage = $response->json('usage');
            Log::info("AI Meal Plan: Day {$day} response received", [
                'prompt_tokens' => $usage['prompt_tokens'] ?? 'N/A',
                'completion_tokens' => $usage['completion_tokens'] ?? 'N/A',
            ]);

            $content = $response->json('choices.0.message.content');
            $parsed = json_decode($content, true);

            $meals = $parsed['meals'] ?? $parsed['plan'] ?? $parsed;
            if (!is_array($meals) || empty($meals)) {
                Log::error("AI Meal Plan: Invalid format for day {$day}", ['content' => $content]);
                return null;
            }

            return $this->processDayMeals($day, $meals);
        } catch (\Exception $e) {
            Log::error("AI Meal Plan: Day {$day} exception", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Process meals for a single day: save recipes to DB, return plan items.
     */
    private function processDayMeals(int $day, array $meals): ?array
    {
        $result = [];

        foreach ($meals as $item) {
            $mealType = $item['meal_type'] ?? null;
            if (!$mealType || !in_array($mealType, ['almusal', 'tanghalian', 'merienda', 'hapunan'])) continue;

            if (!empty($item['recipe'])) {
                $recipeId = $this->saveNewRecipe($item['recipe']);
                if ($recipeId) {
                    $result[] = [
                        'day' => $day,
                        'meal_type' => $mealType,
                        'recipe_id' => $recipeId,
                        'recipe_name' => $item['recipe']['name'] ?? '',
                    ];
                } else {
                    Log::warning("AI Meal Plan: Failed to save recipe", ['day' => $day, 'meal_type' => $mealType, 'name' => $item['recipe']['name'] ?? 'unknown']);
                }
            }
        }

        return !empty($result) ? $result : null;
    }

    /**
     * Save a new AI-generated recipe to the database.
     */
    private function saveNewRecipe(array $data): ?int
    {
        try {
            $difficulty = $data['difficulty'] ?? 'katamtaman';
            if (!in_array($difficulty, ['madali', 'katamtaman', 'mahirap'])) {
                $difficulty = 'katamtaman';
            }

            $mealType = $data['meal_type'] ?? 'tanghalian';
            if (!in_array($mealType, ['almusal', 'tanghalian', 'merienda', 'hapunan'])) {
                $mealType = 'tanghalian';
            }

            $recipe = Recipe::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'meal_type' => $mealType,
                'difficulty' => $difficulty,
                'cook_time_minutes' => $data['cook_time_minutes'] ?? 30,
                'servings' => $data['servings'] ?? 4,
                'calories' => $data['calories'] ?? 0,
                'protein' => $data['protein'] ?? 0,
                'iron' => $data['iron'] ?? 0,
                'vitamin_c' => $data['vitamin_c'] ?? 0,
                'instructions' => $data['instructions'] ?? [],
                'ai_generated' => true,
            ]);

            $ingredientCount = 0;
            if (!empty($data['ingredients'])) {
                foreach ($data['ingredients'] as $ingData) {
                    $ingredient = $this->findOrCreateIngredient($ingData);
                    $recipe->ingredients()->attach($ingredient->id, [
                        'quantity' => $ingData['quantity'] ?? 1,
                        'unit' => $ingData['unit'] ?? 'pc',
                        'estimated_cost' => $ingData['estimated_cost'] ?? 0,
                    ]);
                    $ingredientCount++;
                }
            }

            Log::info('AI Meal Plan: Recipe saved', ['recipe_id' => $recipe->id, 'name' => $recipe->name, 'ingredients' => $ingredientCount]);

            return $recipe->id;
        } catch (\Exception $e) {
            Log::error('Failed to save AI recipe', ['error' => $e->getMessage(), 'data' => $data]);
            return null;
        }
    }

    private const VALID_CATEGORIES = [
        'karne_isda', 'gulay', 'bigas_butil', 'pampalasa',
        'karne', 'isda', 'prutas', 'bigas_at_butil', 'gatas_at_itlog', 'iba_pa',
    ];

    private const CATEGORY_MAP = [
        'meat' => 'karne',
        'fish' => 'isda',
        'seafood' => 'isda',
        'vegetable' => 'gulay',
        'vegetables' => 'gulay',
        'fruit' => 'prutas',
        'fruits' => 'prutas',
        'rice' => 'bigas_butil',
        'grain' => 'bigas_butil',
        'grains' => 'bigas_butil',
        'dairy' => 'gatas_at_itlog',
        'egg' => 'gatas_at_itlog',
        'eggs' => 'gatas_at_itlog',
        'spice' => 'pampalasa',
        'spices' => 'pampalasa',
        'condiment' => 'pampalasa',
        'seasoning' => 'pampalasa',
        'other' => 'iba_pa',
    ];

    /**
     * Find existing ingredient by name (fuzzy) or create a new one.
     */
    private function findOrCreateIngredient(array $data): Ingredient
    {
        $name = trim($data['name']);

        // Try exact match first
        $ingredient = Ingredient::whereRaw('LOWER(name) = ?', [Str::lower($name)])->first();

        if ($ingredient) {
            return $ingredient;
        }

        // Try partial match
        $ingredient = Ingredient::whereRaw('LOWER(name) LIKE ?', ['%' . Str::lower($name) . '%'])->first();

        if ($ingredient) {
            return $ingredient;
        }

        // Sanitize category to a valid ENUM value
        $category = $this->sanitizeCategory($data['category'] ?? 'iba_pa');

        Log::info('AI Meal Plan: Creating new ingredient', ['name' => $name, 'category' => $category]);
        return Ingredient::create([
            'name' => $name,
            'category' => $category,
            'estimated_price' => $data['estimated_cost'] ?? 0,
            'unit' => $data['unit'] ?? 'pc',
        ]);
    }

    /**
     * Map AI-generated category to a valid DB ENUM value.
     */
    private function sanitizeCategory(string $category): string
    {
        $lower = Str::lower(trim($category));

        if (in_array($lower, self::VALID_CATEGORIES)) {
            return $lower;
        }

        if (isset(self::CATEGORY_MAP[$lower])) {
            return self::CATEGORY_MAP[$lower];
        }

        return 'iba_pa';
    }
}
