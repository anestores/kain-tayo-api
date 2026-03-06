<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DietaryRestriction;
use App\Models\UserDietaryRestriction;
use App\Models\UserEquipment;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function setup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'household_count' => 'required|integer|min:1|max:20',
            'household_type' => 'required|in:bata,matatanda,halo-halo',
            'budget' => 'required|numeric|min:300|max:5000',
            'language_code' => 'nullable|string|max:5',
            'dietary_restrictions' => 'nullable|array',
            'dietary_restrictions.*' => 'integer|exists:dietary_restrictions,id',
            'custom_restrictions' => 'nullable|array',
            'custom_restrictions.*' => 'string|max:255',
            'equipment' => 'nullable|array',
            'equipment.*' => 'integer|exists:equipment,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = $request->user();

            $profile = UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'household_count' => $request->household_count,
                    'household_type' => $request->household_type,
                    'budget' => $request->budget,
                    'language_code' => $request->language_code ?? 'fil',
                ]
            );

            // Sync dietary restrictions
            UserDietaryRestriction::where('user_id', $user->id)->delete();

            if ($request->dietary_restrictions) {
                foreach ($request->dietary_restrictions as $restrictionId) {
                    UserDietaryRestriction::create([
                        'user_id' => $user->id,
                        'dietary_restriction_id' => $restrictionId,
                    ]);
                }
            }

            if ($request->custom_restrictions) {
                foreach ($request->custom_restrictions as $customName) {
                    $restriction = DietaryRestriction::firstOrCreate(
                        ['name' => $customName],
                        ['type' => 'lifestyle', 'is_default' => false]
                    );

                    UserDietaryRestriction::create([
                        'user_id' => $user->id,
                        'dietary_restriction_id' => $restriction->id,
                        'custom_name' => $customName,
                    ]);
                }
            }

            // Sync equipment
            UserEquipment::where('user_id', $user->id)->delete();

            if ($request->equipment) {
                foreach ($request->equipment as $equipmentId) {
                    UserEquipment::create([
                        'user_id' => $user->id,
                        'equipment_id' => $equipmentId,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profile setup successful',
                'data' => [
                    'profile' => $profile->fresh(),
                    'dietary_restrictions' => $user->dietaryRestrictions()->get(),
                    'equipment' => $user->equipment()->get(),
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Profile setup failed',
            ], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            $user = $request->user();
            $profile = $user->profile;

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'profile' => $profile,
                    'dietary_restrictions' => $user->dietaryRestrictions()->get(),
                    'equipment' => $user->equipment()->get(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve profile',
            ], 500);
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'household_count' => 'sometimes|integer|min:1|max:20',
            'household_type' => 'sometimes|in:bata,matatanda,halo-halo',
            'budget' => 'sometimes|numeric|min:300|max:5000',
            'language_code' => 'sometimes|nullable|string|max:5',
            'dietary_restrictions' => 'sometimes|nullable|array',
            'dietary_restrictions.*' => 'integer|exists:dietary_restrictions,id',
            'custom_restrictions' => 'sometimes|nullable|array',
            'custom_restrictions.*' => 'string|max:255',
            'equipment' => 'sometimes|nullable|array',
            'equipment.*' => 'integer|exists:equipment,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = $request->user();
            $profile = $user->profile;

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile not found. Please set up your profile first.',
                ], 404);
            }

            $profileFields = $request->only(['household_count', 'household_type', 'budget', 'language_code']);
            if (!empty($profileFields)) {
                $profile->update($profileFields);
            }

            // Sync dietary restrictions if provided
            if ($request->has('dietary_restrictions') || $request->has('custom_restrictions')) {
                UserDietaryRestriction::where('user_id', $user->id)->delete();

                if ($request->dietary_restrictions) {
                    foreach ($request->dietary_restrictions as $restrictionId) {
                        UserDietaryRestriction::create([
                            'user_id' => $user->id,
                            'dietary_restriction_id' => $restrictionId,
                        ]);
                    }
                }

                if ($request->custom_restrictions) {
                    foreach ($request->custom_restrictions as $customName) {
                        $restriction = DietaryRestriction::firstOrCreate(
                            ['name' => $customName],
                            ['type' => 'lifestyle', 'is_default' => false]
                        );

                        UserDietaryRestriction::create([
                            'user_id' => $user->id,
                            'dietary_restriction_id' => $restriction->id,
                            'custom_name' => $customName,
                        ]);
                    }
                }
            }

            // Sync equipment if provided
            if ($request->has('equipment')) {
                UserEquipment::where('user_id', $user->id)->delete();

                if ($request->equipment) {
                    foreach ($request->equipment as $equipmentId) {
                        UserEquipment::create([
                            'user_id' => $user->id,
                            'equipment_id' => $equipmentId,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'profile' => $profile->fresh(),
                    'dietary_restrictions' => $user->dietaryRestrictions()->get(),
                    'equipment' => $user->equipment()->get(),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Profile update failed',
            ], 500);
        }
    }
}
