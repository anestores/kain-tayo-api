<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LanguageController extends Controller
{
    public function index()
    {
        try {
            $languages = Language::where('is_active', true)->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'languages' => $languages,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve languages',
            ], 500);
        }
    }

    public function updatePreference(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'language_code' => 'required|string|exists:languages,code',
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
            $profile = $user->profile;

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile not found. Please set up your profile first.',
                ], 404);
            }

            $profile->update(['language_code' => $request->language_code]);

            return response()->json([
                'success' => true,
                'message' => 'Language preference updated',
                'data' => [
                    'language_code' => $profile->language_code,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update language preference',
            ], 500);
        }
    }
}
