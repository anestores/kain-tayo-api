<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DietaryRestrictionSeeder extends Seeder
{
    public function run(): void
    {
        $restrictions = [
            // Allergies
            ['name' => 'Nuts', 'type' => 'allergy', 'icon' => '🥜', 'is_default' => true],
            ['name' => 'Seafood', 'type' => 'allergy', 'icon' => '🦐', 'is_default' => true],
            ['name' => 'Dairy', 'type' => 'allergy', 'icon' => '🥛', 'is_default' => true],
            ['name' => 'Eggs', 'type' => 'allergy', 'icon' => '🥚', 'is_default' => true],
            ['name' => 'Gluten', 'type' => 'allergy', 'icon' => '🌾', 'is_default' => true],
            ['name' => 'Soy', 'type' => 'allergy', 'icon' => '🫘', 'is_default' => true],

            // Lifestyle preferences
            ['name' => 'Vegan', 'type' => 'lifestyle', 'icon' => '🌱', 'is_default' => true],
            ['name' => 'Vegetarian', 'type' => 'lifestyle', 'icon' => '🥬', 'is_default' => true],
            ['name' => 'Halal', 'type' => 'lifestyle', 'icon' => '☪️', 'is_default' => true],
            ['name' => 'Kosher', 'type' => 'lifestyle', 'icon' => '✡️', 'is_default' => true],
            ['name' => 'Pescatarian', 'type' => 'lifestyle', 'icon' => '🐟', 'is_default' => true],
        ];

        $now = now();

        foreach ($restrictions as $restriction) {
            DB::table('dietary_restrictions')->updateOrInsert(
                ['name' => $restriction['name'], 'type' => $restriction['type']],
                array_merge($restriction, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }
}
