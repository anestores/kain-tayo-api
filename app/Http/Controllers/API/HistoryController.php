<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RecipeHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $history = RecipeHistory::where('user_id', $user->id)
                ->with('mealPlan.items.recipe')
                ->orderBy('saved_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $history,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve history',
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'meal_plan_id' => 'required|exists:meal_plans,id',
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

            $history = RecipeHistory::create([
                'user_id' => $user->id,
                'meal_plan_id' => $request->meal_plan_id,
                'saved_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Meal plan saved to history',
                'data' => [
                    'history' => $history->load('mealPlan'),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save to history',
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();

            $history = RecipeHistory::where('user_id', $user->id)
                ->findOrFail($id);

            $history->delete();

            return response()->json([
                'success' => true,
                'message' => 'History entry deleted',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'History entry not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete history entry',
            ], 500);
        }
    }
}
