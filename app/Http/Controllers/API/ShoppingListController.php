<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShoppingListController extends Controller
{
    public function show($mealPlanId)
    {
        try {
            $shoppingList = ShoppingList::where('meal_plan_id', $mealPlanId)
                ->with('items.ingredient')
                ->firstOrFail();

            // Get budget from user profile
            $mealPlan = \App\Models\MealPlan::find($mealPlanId);
            $budget = 500;
            if ($mealPlan) {
                $profile = \App\Models\UserProfile::where('user_id', $mealPlan->user_id)->first();
                if ($profile) {
                    $budget = $profile->budget;
                }
            }

            // Flatten items for frontend
            $flatItems = $shoppingList->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->ingredient->name ?? 'Unknown',
                    'category' => $item->ingredient->category ?? 'seasonings',
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'estimated_price' => $item->estimated_price,
                    'has_at_home' => (bool) $item->has_at_home,
                    'is_bought' => (bool) $item->is_bought,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $shoppingList->id,
                    'budget' => $budget,
                    'items' => $flatItems,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Shopping list not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve shopping list',
            ], 500);
        }
    }

    public function updateItem(Request $request, $id, $itemId)
    {
        $validator = Validator::make($request->all(), [
            'has_at_home' => 'sometimes|boolean',
            'is_bought' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $shoppingList = ShoppingList::findOrFail($id);

            $item = ShoppingListItem::where('shopping_list_id', $shoppingList->id)
                ->findOrFail($itemId);

            $item->update($request->only(['has_at_home', 'is_bought']));

            $newTotal = $shoppingList->items()
                ->where('has_at_home', false)
                ->sum('estimated_price');

            return response()->json([
                'success' => true,
                'message' => 'Item updated successfully',
                'data' => [
                    'item' => $item->fresh()->load('ingredient'),
                    'total' => round($newTotal, 2),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Shopping list or item not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update item',
            ], 500);
        }
    }

    public function share($id)
    {
        try {
            $shoppingList = ShoppingList::with('items.ingredient')
                ->findOrFail($id);

            $groupedItems = $shoppingList->items->groupBy(function ($item) {
                return $item->ingredient->category;
            });

            $categoryLabels = [
                'karne_isda' => 'Karne at Isda',
                'gulay' => 'Gulay',
                'bigas_butil' => 'Bigas at Butil',
                'pampalasa' => 'Pampalasa',
            ];

            $text = "Shopping List - Kain Tayo Maayos\n";
            $text .= "================================\n\n";

            foreach ($groupedItems as $category => $items) {
                $label = $categoryLabels[$category] ?? $category;
                $text .= strtoupper($label) . "\n";
                $text .= str_repeat('-', strlen($label)) . "\n";

                foreach ($items as $item) {
                    $status = $item->is_bought ? '[x]' : ($item->has_at_home ? '[HOME]' : '[ ]');
                    $text .= "{$status} {$item->ingredient->name} - {$item->quantity} {$item->unit} (P{$item->estimated_price})\n";
                }

                $text .= "\n";
            }

            $total = $shoppingList->items->where('has_at_home', false)->sum('estimated_price');
            $text .= "TOTAL: P" . number_format($total, 2) . "\n";

            return response()->json([
                'success' => true,
                'data' => [
                    'text' => $text,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Shopping list not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate shareable list',
            ], 500);
        }
    }
}
