<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DietaryRestriction;
use App\Models\Equipment;
use App\Models\HouseholdMember;
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
            'dietary_restrictions.*' => 'string|max:255',
            'custom_restrictions' => 'nullable|array',
            'custom_restrictions.*' => 'string|max:255',
            'lifestyle_preferences' => 'nullable|array',
            'lifestyle_preferences.*' => 'string|max:255',
            'equipment' => 'nullable|array',
            'equipment.*' => 'string|max:255',
            'household_members' => 'nullable|array',
            'household_members.*.name' => 'required_with:household_members|string|max:255',
            'household_members.*.gender' => 'required_with:household_members|in:lalaki,babae,iba',
            'household_members.*.age' => 'required_with:household_members|integer|min:0|max:120',
            'household_members.*.activity_level' => 'nullable|in:sedentary,moderate,active,very_active',
            'household_members.*.dietary_restrictions' => 'nullable|array',
            'household_members.*.health_conditions' => 'nullable|array',
            'household_members.*.is_pregnant' => 'nullable|boolean',
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

            $memberCount = $request->household_members ? count($request->household_members) : $request->household_count;

            $profile = UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'household_count' => $memberCount,
                    'household_type' => $request->household_type,
                    'budget' => $request->budget,
                    'language_code' => $request->language_code ?? 'fil',
                ]
            );

            // Sync household members
            if ($request->household_members) {
                HouseholdMember::where('user_id', $user->id)->delete();

                foreach ($request->household_members as $member) {
                    HouseholdMember::create([
                        'user_id' => $user->id,
                        'name' => $member['name'],
                        'gender' => $member['gender'] ?? 'iba',
                        'age' => $member['age'] ?? 25,
                        'activity_level' => $member['activity_level'] ?? 'moderate',
                        'dietary_restrictions' => $member['dietary_restrictions'] ?? [],
                        'health_conditions' => $member['health_conditions'] ?? [],
                        'is_pregnant' => $member['is_pregnant'] ?? false,
                    ]);
                }
            }

            // Sync dietary restrictions (by name)
            UserDietaryRestriction::where('user_id', $user->id)->delete();

            if ($request->dietary_restrictions) {
                foreach ($request->dietary_restrictions as $name) {
                    $restriction = DietaryRestriction::firstOrCreate(
                        ['name' => $name],
                        ['type' => 'allergy', 'is_default' => false]
                    );

                    UserDietaryRestriction::create([
                        'user_id' => $user->id,
                        'dietary_restriction_id' => $restriction->id,
                    ]);
                }
            }

            // Sync lifestyle preferences as dietary restrictions
            if ($request->lifestyle_preferences) {
                foreach ($request->lifestyle_preferences as $name) {
                    $restriction = DietaryRestriction::firstOrCreate(
                        ['name' => $name],
                        ['type' => 'lifestyle', 'is_default' => false]
                    );

                    UserDietaryRestriction::create([
                        'user_id' => $user->id,
                        'dietary_restriction_id' => $restriction->id,
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

            // Sync equipment (by name)
            UserEquipment::where('user_id', $user->id)->delete();

            if ($request->equipment) {
                foreach ($request->equipment as $name) {
                    $equip = Equipment::firstOrCreate(
                        ['name' => $name],
                        ['is_default' => false]
                    );

                    UserEquipment::create([
                        'user_id' => $user->id,
                        'equipment_id' => $equip->id,
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
                    'household_members' => $user->householdMembers()->get(),
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Profile setup failed: ' . $e->getMessage(),
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
                    'household_members' => $user->householdMembers()->get(),
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
            'dietary_restrictions.*' => 'string|max:255',
            'custom_restrictions' => 'sometimes|nullable|array',
            'custom_restrictions.*' => 'string|max:255',
            'lifestyle_preferences' => 'sometimes|nullable|array',
            'lifestyle_preferences.*' => 'string|max:255',
            'equipment' => 'sometimes|nullable|array',
            'equipment.*' => 'string|max:255',
            'household_members' => 'sometimes|nullable|array',
            'household_members.*.name' => 'required_with:household_members|string|max:255',
            'household_members.*.gender' => 'required_with:household_members|in:lalaki,babae,iba',
            'household_members.*.age' => 'required_with:household_members|integer|min:0|max:120',
            'household_members.*.activity_level' => 'nullable|in:sedentary,moderate,active,very_active',
            'household_members.*.dietary_restrictions' => 'nullable|array',
            'household_members.*.health_conditions' => 'nullable|array',
            'household_members.*.is_pregnant' => 'nullable|boolean',
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
            if ($request->has('dietary_restrictions') || $request->has('custom_restrictions') || $request->has('lifestyle_preferences')) {
                UserDietaryRestriction::where('user_id', $user->id)->delete();

                if ($request->dietary_restrictions) {
                    foreach ($request->dietary_restrictions as $name) {
                        $restriction = DietaryRestriction::firstOrCreate(
                            ['name' => $name],
                            ['type' => 'allergy', 'is_default' => false]
                        );

                        UserDietaryRestriction::create([
                            'user_id' => $user->id,
                            'dietary_restriction_id' => $restriction->id,
                        ]);
                    }
                }

                if ($request->lifestyle_preferences) {
                    foreach ($request->lifestyle_preferences as $name) {
                        $restriction = DietaryRestriction::firstOrCreate(
                            ['name' => $name],
                            ['type' => 'lifestyle', 'is_default' => false]
                        );

                        UserDietaryRestriction::create([
                            'user_id' => $user->id,
                            'dietary_restriction_id' => $restriction->id,
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

            // Sync household members if provided
            if ($request->has('household_members')) {
                HouseholdMember::where('user_id', $user->id)->delete();

                if ($request->household_members) {
                    foreach ($request->household_members as $member) {
                        HouseholdMember::create([
                            'user_id' => $user->id,
                            'name' => $member['name'],
                            'gender' => $member['gender'] ?? 'iba',
                            'age' => $member['age'] ?? 25,
                            'activity_level' => $member['activity_level'] ?? 'moderate',
                            'dietary_restrictions' => $member['dietary_restrictions'] ?? [],
                            'health_conditions' => $member['health_conditions'] ?? [],
                            'is_pregnant' => $member['is_pregnant'] ?? false,
                        ]);
                    }

                    // Update household_count to match members
                    $profile->update(['household_count' => count($request->household_members)]);
                }
            }

            // Sync equipment if provided
            if ($request->has('equipment')) {
                UserEquipment::where('user_id', $user->id)->delete();

                if ($request->equipment) {
                    foreach ($request->equipment as $name) {
                        $equip = Equipment::firstOrCreate(
                            ['name' => $name],
                            ['is_default' => false]
                        );

                        UserEquipment::create([
                            'user_id' => $user->id,
                            'equipment_id' => $equip->id,
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
