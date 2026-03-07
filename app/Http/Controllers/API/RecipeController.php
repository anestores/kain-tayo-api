<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Recipe::with(['ingredients', 'equipment']);

            if ($request->has('meal_type') && $request->meal_type !== 'all') {
                $query->where('meal_type', $request->meal_type);
            }

            if ($request->has('search') && $request->search) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            $recipes = $query->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'recipes' => $recipes,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve recipes',
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $recipe = Recipe::with(['ingredients', 'equipment', 'dietaryFlags'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'recipe' => $recipe,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Recipe not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve recipe',
            ], 500);
        }
    }
}
