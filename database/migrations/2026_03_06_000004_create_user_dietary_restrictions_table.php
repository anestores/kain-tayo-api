<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_dietary_restrictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('dietary_restriction_id')->constrained('dietary_restrictions')->cascadeOnDelete();
            $table->string('custom_name')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'dietary_restriction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_dietary_restrictions');
    }
};
