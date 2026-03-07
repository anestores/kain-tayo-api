<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE ingredients MODIFY COLUMN category ENUM('karne_isda', 'gulay', 'bigas_butil', 'pampalasa', 'karne', 'isda', 'prutas', 'bigas_at_butil', 'gatas_at_itlog', 'iba_pa') NOT NULL DEFAULT 'iba_pa'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE ingredients MODIFY COLUMN category ENUM('karne_isda', 'gulay', 'bigas_butil', 'pampalasa') NOT NULL");
    }
};
