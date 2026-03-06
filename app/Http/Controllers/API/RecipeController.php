<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Recipe;

class RecipeController extends Controller
{
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
