<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IngredientSeeder extends Seeder
{
    public function run(): void
    {
        $ingredients = [
            // karne_isda (Meat & Fish)
            ['name' => 'Chicken (whole)', 'category' => 'karne_isda', 'estimated_price' => 180.00, 'unit' => 'kg'],
            ['name' => 'Chicken breast', 'category' => 'karne_isda', 'estimated_price' => 200.00, 'unit' => 'kg'],
            ['name' => 'Chicken thigh', 'category' => 'karne_isda', 'estimated_price' => 170.00, 'unit' => 'kg'],
            ['name' => 'Pork belly (liempo)', 'category' => 'karne_isda', 'estimated_price' => 280.00, 'unit' => 'kg'],
            ['name' => 'Pork shoulder (kasim)', 'category' => 'karne_isda', 'estimated_price' => 250.00, 'unit' => 'kg'],
            ['name' => 'Ground pork', 'category' => 'karne_isda', 'estimated_price' => 220.00, 'unit' => 'kg'],
            ['name' => 'Beef (brisket)', 'category' => 'karne_isda', 'estimated_price' => 350.00, 'unit' => 'kg'],
            ['name' => 'Tilapia', 'category' => 'karne_isda', 'estimated_price' => 120.00, 'unit' => 'kg'],
            ['name' => 'Galunggong (mackerel)', 'category' => 'karne_isda', 'estimated_price' => 150.00, 'unit' => 'kg'],
            ['name' => 'Bangus (milkfish)', 'category' => 'karne_isda', 'estimated_price' => 180.00, 'unit' => 'kg'],
            ['name' => 'Dried fish (tuyo)', 'category' => 'karne_isda', 'estimated_price' => 80.00, 'unit' => 'pack'],
            ['name' => 'Shrimp', 'category' => 'karne_isda', 'estimated_price' => 350.00, 'unit' => 'kg'],
            ['name' => 'Squid (pusit)', 'category' => 'karne_isda', 'estimated_price' => 250.00, 'unit' => 'kg'],
            ['name' => 'Sardines (canned)', 'category' => 'karne_isda', 'estimated_price' => 18.00, 'unit' => 'can'],
            ['name' => 'Corned beef (canned)', 'category' => 'karne_isda', 'estimated_price' => 45.00, 'unit' => 'can'],
            ['name' => 'Eggs', 'category' => 'karne_isda', 'estimated_price' => 8.00, 'unit' => 'piece'],

            // gulay (Vegetables)
            ['name' => 'Kangkong', 'category' => 'gulay', 'estimated_price' => 15.00, 'unit' => 'bundle'],
            ['name' => 'Pechay', 'category' => 'gulay', 'estimated_price' => 20.00, 'unit' => 'bundle'],
            ['name' => 'Sitaw (string beans)', 'category' => 'gulay', 'estimated_price' => 30.00, 'unit' => 'bundle'],
            ['name' => 'Talong (eggplant)', 'category' => 'gulay', 'estimated_price' => 15.00, 'unit' => 'piece'],
            ['name' => 'Kalabasa (squash)', 'category' => 'gulay', 'estimated_price' => 40.00, 'unit' => 'kg'],
            ['name' => 'Kamote (sweet potato)', 'category' => 'gulay', 'estimated_price' => 35.00, 'unit' => 'kg'],
            ['name' => 'Sayote (chayote)', 'category' => 'gulay', 'estimated_price' => 20.00, 'unit' => 'piece'],
            ['name' => 'Ampalaya (bitter gourd)', 'category' => 'gulay', 'estimated_price' => 30.00, 'unit' => 'piece'],
            ['name' => 'Cabbage (repolyo)', 'category' => 'gulay', 'estimated_price' => 50.00, 'unit' => 'head'],
            ['name' => 'Carrots', 'category' => 'gulay', 'estimated_price' => 60.00, 'unit' => 'kg'],
            ['name' => 'Potatoes', 'category' => 'gulay', 'estimated_price' => 70.00, 'unit' => 'kg'],
            ['name' => 'Tomatoes (kamatis)', 'category' => 'gulay', 'estimated_price' => 40.00, 'unit' => 'kg'],
            ['name' => 'Onion (sibuyas)', 'category' => 'gulay', 'estimated_price' => 60.00, 'unit' => 'kg'],
            ['name' => 'Garlic (bawang)', 'category' => 'gulay', 'estimated_price' => 100.00, 'unit' => 'kg'],
            ['name' => 'Ginger (luya)', 'category' => 'gulay', 'estimated_price' => 80.00, 'unit' => 'kg'],
            ['name' => 'Malunggay leaves', 'category' => 'gulay', 'estimated_price' => 10.00, 'unit' => 'bundle'],
            ['name' => 'Monggo (mung beans)', 'category' => 'gulay', 'estimated_price' => 60.00, 'unit' => 'kg'],

            // bigas_butil (Rice & Grains)
            ['name' => 'Rice (bigas)', 'category' => 'bigas_butil', 'estimated_price' => 45.00, 'unit' => 'kg'],
            ['name' => 'Pancit canton noodles', 'category' => 'bigas_butil', 'estimated_price' => 12.00, 'unit' => 'pack'],
            ['name' => 'Misua noodles', 'category' => 'bigas_butil', 'estimated_price' => 25.00, 'unit' => 'pack'],
            ['name' => 'Bihon noodles', 'category' => 'bigas_butil', 'estimated_price' => 30.00, 'unit' => 'pack'],
            ['name' => 'Bread (pandesal)', 'category' => 'bigas_butil', 'estimated_price' => 2.00, 'unit' => 'piece'],
            ['name' => 'Oatmeal', 'category' => 'bigas_butil', 'estimated_price' => 50.00, 'unit' => 'pack'],
            ['name' => 'Cornstarch', 'category' => 'bigas_butil', 'estimated_price' => 15.00, 'unit' => 'pack'],
            ['name' => 'Flour (all-purpose)', 'category' => 'bigas_butil', 'estimated_price' => 45.00, 'unit' => 'kg'],

            // pampalasa (Condiments & Seasonings)
            ['name' => 'Soy sauce (toyo)', 'category' => 'pampalasa', 'estimated_price' => 15.00, 'unit' => 'bottle'],
            ['name' => 'Vinegar (suka)', 'category' => 'pampalasa', 'estimated_price' => 12.00, 'unit' => 'bottle'],
            ['name' => 'Fish sauce (patis)', 'category' => 'pampalasa', 'estimated_price' => 18.00, 'unit' => 'bottle'],
            ['name' => 'Cooking oil', 'category' => 'pampalasa', 'estimated_price' => 40.00, 'unit' => 'bottle'],
            ['name' => 'Calamansi', 'category' => 'pampalasa', 'estimated_price' => 5.00, 'unit' => 'piece'],
            ['name' => 'Salt (asin)', 'category' => 'pampalasa', 'estimated_price' => 10.00, 'unit' => 'pack'],
            ['name' => 'Pepper (paminta)', 'category' => 'pampalasa', 'estimated_price' => 8.00, 'unit' => 'pack'],
            ['name' => 'Bay leaves (laurel)', 'category' => 'pampalasa', 'estimated_price' => 10.00, 'unit' => 'pack'],
            ['name' => 'Peanut butter', 'category' => 'pampalasa', 'estimated_price' => 45.00, 'unit' => 'jar'],
            ['name' => 'Coconut milk (gata)', 'category' => 'pampalasa', 'estimated_price' => 35.00, 'unit' => 'can'],
            ['name' => 'Sugar', 'category' => 'pampalasa', 'estimated_price' => 55.00, 'unit' => 'kg'],
            ['name' => 'Oyster sauce', 'category' => 'pampalasa', 'estimated_price' => 30.00, 'unit' => 'bottle'],
        ];

        $now = now();

        foreach ($ingredients as $ingredient) {
            DB::table('ingredients')->updateOrInsert(
                ['name' => $ingredient['name']],
                array_merge($ingredient, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }
}
