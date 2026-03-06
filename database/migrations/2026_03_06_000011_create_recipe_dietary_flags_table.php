<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_dietary_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->foreignId('dietary_restriction_id')->constrained('dietary_restrictions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['recipe_id', 'dietary_restriction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_dietary_flags');
    }
};
