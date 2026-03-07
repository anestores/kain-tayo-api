<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\MobileAuthController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\LanguageController;
use App\Http\Controllers\API\MealPlanController;
use App\Http\Controllers\API\ShoppingListController;
use App\Http\Controllers\API\MarketController;
use App\Http\Controllers\API\RecipeController;
use App\Http\Controllers\API\HistoryController;

// Public routes
Route::prefix('mobile')->group(function () {
    // Auth
    Route::post('/login', [MobileAuthController::class, 'login']);
    Route::post('/register', [MobileAuthController::class, 'register']);

    // Languages
    Route::get('/languages', [LanguageController::class, 'index']);
});

// Protected routes
Route::prefix('mobile')->middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [MobileAuthController::class, 'logout']);
    Route::get('/me', [MobileAuthController::class, 'me']);

    // Profile
    Route::post('/profile/setup', [ProfileController::class, 'setup']);
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);

    // Language preference
    Route::put('/user/language', [LanguageController::class, 'updatePreference']);

    // Meal Plans
    Route::get('/meal-plan/active', [MealPlanController::class, 'active']);
    Route::post('/meal-plan/generate', [MealPlanController::class, 'generate']);
    Route::get('/meal-plan/{id}', [MealPlanController::class, 'show']);
    Route::delete('/meal-plan/{id}', [MealPlanController::class, 'destroy']);
    Route::post('/meal-plan/{id}/swap', [MealPlanController::class, 'swap']);
    Route::post('/meal-plan/{id}/add', [MealPlanController::class, 'addMeal']);
    Route::get('/meal-plan/{id}/alternatives', [MealPlanController::class, 'alternatives']);

    // Shopping Lists
    Route::get('/meal-plan/{mealPlanId}/shopping-list', [ShoppingListController::class, 'show']);
    Route::put('/shopping-list/{id}/items/{itemId}', [ShoppingListController::class, 'updateItem']);
    Route::get('/shopping-list/{id}/share', [ShoppingListController::class, 'share']);

    // Markets
    Route::get('/markets/favorites', [MarketController::class, 'favorites']);
    Route::get('/markets', [MarketController::class, 'index']);
    Route::get('/markets/{id}', [MarketController::class, 'show']);
    Route::post('/markets/{id}/favorite', [MarketController::class, 'favorite']);
    Route::delete('/markets/{id}/favorite', [MarketController::class, 'unfavorite']);

    // Recipes
    Route::get('/recipes', [RecipeController::class, 'index']);
    Route::get('/recipes/{id}', [RecipeController::class, 'show']);

    // Recipe History
    Route::get('/recipe-history', [HistoryController::class, 'index']);
    Route::post('/recipe-history', [HistoryController::class, 'store']);
    Route::delete('/recipe-history/{id}', [HistoryController::class, 'destroy']);
});
