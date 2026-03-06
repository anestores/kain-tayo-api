<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dietary_restrictions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['allergy', 'lifestyle']);
            $table->string('icon')->nullable();
            $table->boolean('is_default')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dietary_restrictions');
    }
};
