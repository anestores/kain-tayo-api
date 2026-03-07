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
    /**
     * Generate a meal plan using OpenAI — all recipes are AI-created.
     * Returns array of ['day' => int, 'meal_type' => string, 'recipe_id' => int] or null on failure.
     */
    public function generatePlan(User $user, string $prompt): ?array
    {
        try {
            $profile = $user->profile;
            $members = $user->householdMembers()->get();
            $restrictions = $user->dietaryRestrictions()->pluck('name')->toArray();
            $equipment = $user->equipment()->pluck('name')->toArray();

            // Build household description
            $householdDesc = $members->map(function ($m) {
                $desc = "{$m->name}: {$m->gender}, {$m->age} yrs, {$m->activity_level}";
                if ($m->is_pregnant) $desc .= ', pregnant';
                if (!empty($m->dietary_restrictions)) $desc .= ', restrictions: ' . implode(', ', $m->dietary_restrictions);
                if (!empty($m->health_conditions)) $desc .= ', conditions: ' . implode(', ', $m->health_conditions);
                return $desc;
            })->implode("\n");

            $systemPrompt = <<<'SYSTEM'
You are a Filipino meal planning assistant for PlanTipid, a budget meal planning app.

Create a 7-day meal plan with 4 meals per day (almusal, tanghalian, merienda, hapunan = 28 total).
Every recipe must be a REAL Filipino dish with complete details.

RULES:
- All recipes must be realistic, culturally appropriate Filipino dishes
- Include accurate nutrition estimates (calories, protein in g, iron in mg, vitamin_c in mg)
- Ingredient costs must be realistic Philippine market prices in pesos
- Respect dietary restrictions, health conditions, and budget
- Minimize repetition — use different recipes each day
- meal_type must be one of: almusal, tanghalian, merienda, hapunan
- difficulty must be one of: madali, katamtaman, mahirap
- Ingredient categories: karne, isda, gulay, prutas, bigas_at_butil, gatas_at_itlog, pampalasa, iba_pa

Return ONLY valid JSON with this structure:
{
  "plan": [
    {
      "day": 1,
      "meal_type": "almusal",
      "recipe": {
        "name": "Champorado",
        "description": "Sweet chocolate rice porridge",
        "meal_type": "almusal",
        "difficulty": "madali",
        "cook_time_minutes": 25,
        "servings": 4,
        "calories": 280.00,
        "protein": 6.50,
        "iron": 1.80,
        "vitamin_c": 0.50,
        "instructions": ["Pakuluan ang bigas sa tubig.", "Idagdag ang cocoa powder at asukal.", "Haluin hanggang lumapot."],
        "ingredients": [
          {"name": "Bigas (malagkit)", "category": "bigas_at_butil", "quantity": 200, "unit": "g", "estimated_cost": 25.00},
          {"name": "Cocoa powder", "category": "pampalasa", "quantity": 30, "unit": "g", "estimated_cost": 15.00},
          {"name": "Asukal", "category": "pampalasa", "quantity": 50, "unit": "g", "estimated_cost": 5.00}
        ]
      }
    }
  ]
}

You must return exactly 28 items (7 days x 4 meals). Every item must have a full "recipe" object.
SYSTEM;

            $userMessage = "HOUSEHOLD:\n{$householdDesc}\n\n"
                . "Budget: ₱" . number_format($profile->budget ?? 500, 0) . "/week\n"
                . "Equipment: " . (empty($equipment) ? 'basic kitchen' : implode(', ', $equipment)) . "\n"
                . "Dietary restrictions: " . (empty($restrictions) ? 'none' : implode(', ', $restrictions)) . "\n\n"
                . "USER REQUEST: {$prompt}";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                'Content-Type' => 'application/json',
            ])->timeout(120)->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model', 'gpt-4o-mini'),
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'temperature' => 0.7,
                'max_tokens' => 16000,
                'response_format' => ['type' => 'json_object'],
            ]);

            if (!$response->successful()) {
                Log::error('OpenAI API error', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            $content = $response->json('choices.0.message.content');
            $parsed = json_decode($content, true);

            // Extract the plan array
            $plan = $parsed['plan'] ?? $parsed['meal_plan'] ?? $parsed['meals'] ?? $parsed;
            if (!is_array($plan) || empty($plan)) {
                Log::error('OpenAI returned invalid format', ['content' => $content]);
                return null;
            }

            // Process: save all new recipes to DB
            return $this->processPlan($plan);
        } catch (\Exception $e) {
            Log::error('AI meal plan generation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return null;
        }
    }

    /**
     * Process AI plan: save all recipes to DB, return normalized plan with recipe_ids.
     */
    private function processPlan(array $plan): ?array
    {
        $result = [];

        foreach ($plan as $item) {
            $day = $item['day'] ?? null;
            $mealType = $item['meal_type'] ?? null;

            if (!$day || !$mealType) continue;
            if (!in_array($mealType, ['almusal', 'tanghalian', 'merienda', 'hapunan'])) continue;

            if (!empty($item['recipe'])) {
                $recipeId = $this->saveNewRecipe($item['recipe']);
                if ($recipeId) {
                    $result[] = ['day' => $day, 'meal_type' => $mealType, 'recipe_id' => $recipeId];
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

            // Save ingredients
            if (!empty($data['ingredients'])) {
                foreach ($data['ingredients'] as $ingData) {
                    $ingredient = $this->findOrCreateIngredient($ingData);
                    $recipe->ingredients()->attach($ingredient->id, [
                        'quantity' => $ingData['quantity'] ?? 1,
                        'unit' => $ingData['unit'] ?? 'pc',
                        'estimated_cost' => $ingData['estimated_cost'] ?? 0,
                    ]);
                }
            }

            return $recipe->id;
        } catch (\Exception $e) {
            Log::error('Failed to save AI recipe', ['error' => $e->getMessage(), 'data' => $data]);
            return null;
        }
    }

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

        // Create new ingredient
        return Ingredient::create([
            'name' => $name,
            'category' => $data['category'] ?? 'iba_pa',
            'estimated_price' => $data['estimated_cost'] ?? 0,
            'unit' => $data['unit'] ?? 'pc',
        ]);
    }
}
