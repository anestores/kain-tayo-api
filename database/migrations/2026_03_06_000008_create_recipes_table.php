<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('meal_type', ['almusal', 'tanghalian', 'merienda', 'hapunan']);
            $table->enum('difficulty', ['madali', 'katamtaman', 'mahirap']);
            $table->integer('cook_time_minutes');
            $table->integer('servings')->default(4);
            $table->decimal('calories', 8, 2)->nullable();
            $table->decimal('protein', 8, 2)->nullable();
            $table->decimal('iron', 8, 2)->nullable();
            $table->decimal('vitamin_c', 8, 2)->nullable();
            $table->json('instructions');
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
