<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Market;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MarketController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'sort_by' => 'nullable|in:distance,price,rating',
            'radius' => 'nullable|numeric|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $lat = $request->query('lat');
            $lng = $request->query('lng');
            $sortBy = $request->query('sort_by', 'distance');
            $radius = $request->query('radius', 25);

            // Haversine formula for distance in kilometers
            $haversine = "(6371 * acos(
                cos(radians(?)) * cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) +
                sin(radians(?)) * sin(radians(latitude))
            ))";

            $query = Market::select('markets.*')
                ->selectRaw("{$haversine} AS distance", [$lat, $lng, $lat])
                ->whereRaw("{$haversine} < ?", [$lat, $lng, $lat, $radius]);

            switch ($sortBy) {
                case 'price':
                    $query->orderBy('price_range', 'asc');
                    break;
                case 'rating':
                    $query->orderBy('rating', 'desc');
                    break;
                case 'distance':
                default:
                    $query->orderBy('distance', 'asc');
                    break;
            }

            $markets = $query->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'markets' => $markets,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve markets',
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $market = Market::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'market' => $market,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Market not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve market',
            ], 500);
        }
    }

    public function favorite(Request $request, $id)
    {
        try {
            $user = $request->user();
            $market = Market::findOrFail($id);

            $user->favoriteMarkets()->syncWithoutDetaching([$market->id]);

            return response()->json([
                'success' => true,
                'message' => 'Market added to favorites',
                'data' => [
                    'market' => $market,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Market not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add market to favorites',
            ], 500);
        }
    }

    public function unfavorite(Request $request, $id)
    {
        try {
            $user = $request->user();
            $market = Market::findOrFail($id);

            $user->favoriteMarkets()->detach($market->id);

            return response()->json([
                'success' => true,
                'message' => 'Market removed from favorites',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Market not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove market from favorites',
            ], 500);
        }
    }

    public function favorites(Request $request)
    {
        try {
            $user = $request->user();
            $markets = $user->favoriteMarkets()->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'markets' => $markets,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve favorite markets',
            ], 500);
        }
    }
}
