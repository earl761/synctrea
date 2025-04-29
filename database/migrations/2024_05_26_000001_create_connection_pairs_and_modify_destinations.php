<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Drop tables if they exist to ensure clean state
        Schema::dropIfExists('connection_pairs');
        Schema::dropIfExists('destinations');

        // Create destinations table
        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // 'amazon', 'prestashop'
            $table->string('region')->nullable(); // For Amazon regions (US, EU, etc.)
            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            $table->string('api_endpoint')->nullable();
            $table->json('credentials')->nullable(); // Additional API credentials
            $table->json('settings')->nullable(); // Destination-specific settings
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Create connection_pairs table
        Schema::create('connection_pairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('destination_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Ensure unique supplier-destination pairs
            $table->unique(['supplier_id', 'destination_id']);
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        // First drop the connection_pairs table since it depends on destinations
        Schema::dropIfExists('connection_pairs');
        
        // Then drop the destinations table
        Schema::dropIfExists('destinations');

        Schema::enableForeignKeyConstraints();
    }
};