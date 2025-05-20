<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // 'amazon', 'prestashop'
            $table->string('region')->nullable(); // For Amazon regions (US, EU, etc.)
            $table->text('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->text('api_endpoint')->nullable();
            $table->json('credentials')->nullable(); // Additional API credentials
            $table->json('settings')->nullable(); // Destination-specific settings
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destinations');
    }
};