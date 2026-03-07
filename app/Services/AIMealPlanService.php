<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIMealPlanService
{
    public function generatePlan(User $user, string $prompt): ?array
    {
        try {
            $profile = $user->profile;
            $members = $user->householdMembers()->get();
            $restrictions = $user->dietaryRestrictions()->pluck('name')->toArray();
            $equipment = $user->equipment()->pluck('name')->toArray();

            // Get all recipes with minimal data for the AI
            $recipes = Recipe::select('id', 'name', 'meal_type', 'difficulty', 'calories', 'protein', 'iron', 'vitamin_c', 'cook_time_minutes', 'servings')
                ->with(['ingredients:id,name', 'dietaryFlags:id,name'])
                ->get()
                ->map(function ($r) {
                    $cost = $r->ingredients()->sum('recipe_ingredients.estimated_cost');
                    return [
                        'id' => $r->id,
                        'name' => $r->name,
                        'meal_type' => $r->meal_type,
                        'difficulty' => $r->difficulty,
                        'calories' => (float) $r->calories,
                        'protein' => (float) $r->protein,
                        'iron' => (float) $r->iron,
                        'vitamin_c' => (float) $r->vitamin_c,
                        'cook_time_minutes' => $r->cook_time_minutes,
                        'cost' => round($cost, 2),
                        'flags' => $r->dietaryFlags->pluck('name')->toArray(),
                    ];
                });

            // Build household description
            $householdDesc = $members->map(function ($m) {
                $desc = "{$m->name}: {$m->gender}, {$m->age} yrs, {$m->activity_level}";
                if ($m->is_pregnant) $desc .= ', pregnant';
                if (!empty($m->dietary_restrictions)) $desc .= ', restrictions: ' . implode(', ', $m->dietary_restrictions);
                if (!empty($m->health_conditions)) $desc .= ', conditions: ' . implode(', ', $m->health_conditions);
                return $desc;
            })->implode("\n");

            $systemPrompt = <<<SYSTEM
You are a Filipino meal planning assistant for PlanTipid, a budget meal planning app.
You must select recipes from the provided list to create a 7-day meal plan (4 meals per day: almusal, tanghalian, merienda, hapunan).

Rules:
- ONLY use recipe IDs from the provided list
- Match recipe meal_type to the meal slot (almusal recipes for almusal, etc.)
- Respect dietary restrictions and health conditions
- Stay within the weekly budget
- Ensure nutritional variety across the week
- Minimize recipe repetition (try to use different recipes each day)
- Consider the household members' ages and conditions

Return ONLY valid JSON, no explanation. Format:
[{"day":1,"meal_type":"almusal","recipe_id":5},{"day":1,"meal_type":"tanghalian","recipe_id":12},...]

You must return exactly 28 items (7 days x 4 meals).
SYSTEM;

            $userMessage = "HOUSEHOLD:\n{$householdDesc}\n\n"
                . "Budget: ₱" . number_format($profile->budget ?? 500, 0) . "/week\n"
                . "Equipment: " . (empty($equipment) ? 'basic kitchen' : implode(', ', $equipment)) . "\n"
                . "Dietary restrictions: " . (empty($restrictions) ? 'none' : implode(', ', $restrictions)) . "\n\n"
                . "USER REQUEST: {$prompt}\n\n"
                . "AVAILABLE RECIPES:\n" . json_encode($recipes, JSON_UNESCAPED_UNICODE);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model', 'gpt-4o-mini'),
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'temperature' => 0.7,
                'response_format' => ['type' => 'json_object'],
            ]);

            if (!$response->successful()) {
                Log::error('OpenAI API error', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            $content = $response->json('choices.0.message.content');
            $parsed = json_decode($content, true);

            // Handle both direct array and wrapped object responses
            if (isset($parsed['meal_plan'])) {
                $parsed = $parsed['meal_plan'];
            } elseif (isset($parsed['meals'])) {
                $parsed = $parsed['meals'];
            } elseif (isset($parsed['plan'])) {
                $parsed = $parsed['plan'];
            }

            if (!is_array($parsed) || empty($parsed)) {
                Log::error('OpenAI returned invalid meal plan format', ['content' => $content]);
                return null;
            }

            // Validate all recipe IDs exist
            $validRecipeIds = Recipe::pluck('id')->toArray();
            foreach ($parsed as $item) {
                if (!isset($item['day'], $item['meal_type'], $item['recipe_id'])) {
                    Log::error('OpenAI returned malformed item', ['item' => $item]);
                    return null;
                }
                if (!in_array($item['recipe_id'], $validRecipeIds)) {
                    Log::error('OpenAI returned invalid recipe_id', ['recipe_id' => $item['recipe_id']]);
                    return null;
                }
            }

            return $parsed;
        } catch (\Exception $e) {
            Log::error('AI meal plan generation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
