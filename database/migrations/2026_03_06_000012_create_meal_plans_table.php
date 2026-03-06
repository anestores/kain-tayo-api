<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->date('week_start_date');
            $table->decimal('total_cost', 8, 2)->nullable();
            $table->enum('status', ['draft', 'active', 'completed'])->default('active');
            $table->timestamps();

            $table->index(['user_id', 'week_start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_plans');
    }
};
