<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('household_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->enum('gender', ['lalaki', 'babae', 'iba'])->default('iba');
            $table->integer('age')->default(25);
            $table->enum('activity_level', ['sedentary', 'moderate', 'active', 'very_active'])->default('moderate');
            $table->json('dietary_restrictions')->nullable();
            $table->json('health_conditions')->nullable();
            $table->boolean('is_pregnant')->default(false);
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('household_members');
    }
};
