<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['code' => 'fil', 'name' => 'Filipino', 'native_name' => 'Wikang Filipino', 'is_active' => true],
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true],
            ['code' => 'bis', 'name' => 'Bisaya', 'native_name' => 'Sinugbuanong Binisaya', 'is_active' => true],
            ['code' => 'ilo', 'name' => 'Ilocano', 'native_name' => 'Pagsasao nga Ilokano', 'is_active' => true],
        ];

        $now = now();

        foreach ($languages as $language) {
            DB::table('languages')->updateOrInsert(
                ['code' => $language['code']],
                array_merge($language, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }
}
