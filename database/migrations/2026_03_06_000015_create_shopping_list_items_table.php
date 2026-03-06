<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopping_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopping_list_id')->constrained('shopping_lists')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->decimal('quantity', 8, 2);
            $table->string('unit');
            $table->decimal('estimated_price', 8, 2);
            $table->boolean('has_at_home')->default(false);
            $table->boolean('is_bought')->default(false);
            $table->timestamps();

            $table->index('shopping_list_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopping_list_items');
    }
};
