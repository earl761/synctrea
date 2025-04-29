<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // e.g., 'ingram_micro', 'other'
            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            $table->string('api_endpoint')->nullable();
            $table->json('credentials')->nullable(); // Store additional API credentials
            $table->json('settings')->nullable(); // Store supplier-specific settings
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};