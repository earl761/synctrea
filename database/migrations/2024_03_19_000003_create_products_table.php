<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->index();
            $table->string('upc')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('brand')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('category')->nullable();
            $table->decimal('cost_price', 10, 2);
            $table->decimal('retail_price', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->string('condition')->default('new');
            $table->string('status')->default('active');
            $table->json('specifications')->nullable();
            $table->json('dimensions')->nullable();
            $table->json('images')->nullable();
            $table->json('metadata')->nullable(); // For additional supplier-specific data
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['supplier_id', 'sku']);
        });

        // Create product_destination pivot table for marketplace listings
        Schema::create('product_destination', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('destination_id')->constrained()->cascadeOnDelete();
            $table->string('destination_sku')->nullable(); // Marketplace-specific SKU
            $table->string('marketplace_product_id')->nullable(); // Marketplace-specific ID
            $table->decimal('sale_price', 10, 2);
            $table->json('pricing_rules')->nullable(); // Store dynamic pricing rules
            $table->json('marketplace_data')->nullable(); // Store marketplace-specific data
            $table->string('sync_status')->default('pending');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'destination_id']);
            $table->index('destination_sku');
            $table->index('sync_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_destination');
        Schema::dropIfExists('products');
    }
};