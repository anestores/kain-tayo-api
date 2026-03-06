<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_plan_id')->constrained('meal_plans')->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->tinyInteger('day_number');
            $table->enum('meal_type', ['almusal', 'tanghalian', 'merienda', 'hapunan']);
            $table->timestamps();

            $table->index(['meal_plan_id', 'day_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_plan_items');
    }
};
