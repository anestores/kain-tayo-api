<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $equipment = [
            ['name' => 'Kalan', 'icon' => '🔥', 'is_default' => true],
            ['name' => 'Rice Cooker', 'icon' => '🍚', 'is_default' => true],
            ['name' => 'Oven', 'icon' => '♨️', 'is_default' => true],
            ['name' => 'Microwave', 'icon' => '📡', 'is_default' => true],
            ['name' => 'Ref', 'icon' => '❄️', 'is_default' => true],
            ['name' => 'Lutuan sa Uling', 'icon' => '🪵', 'is_default' => true],
            ['name' => 'Kawali', 'icon' => '🍳', 'is_default' => true],
            ['name' => 'Palayok', 'icon' => '🥘', 'is_default' => true],
            ['name' => 'Steamer', 'icon' => '♨️', 'is_default' => true],
        ];

        $now = now();

        foreach ($equipment as $item) {
            DB::table('equipment')->updateOrInsert(
                ['name' => $item['name']],
                array_merge($item, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }
}
