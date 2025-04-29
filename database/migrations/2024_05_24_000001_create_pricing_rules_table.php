<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // global_connection, product_specific
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('destination_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete(); // Only for product-specific rules
            $table->string('rule_type'); // percentage_markup, flat_markup, tiered
            $table->json('conditions')->nullable(); // For tiered pricing conditions
            $table->decimal('value', 10, 2)->nullable(); // For flat or percentage markup
            $table->json('tiers')->nullable(); // For tiered pricing rules
            $table->integer('priority')->default(0); // Higher priority rules are applied first
            $table->boolean('is_active')->default(true);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();

            // Ensure uniqueness for global connection rules
            $table->unique(['supplier_id', 'destination_id', 'type'], 'unique_global_rule');
            // Ensure uniqueness for product-specific rules
            $table->unique(['supplier_id', 'destination_id', 'product_id', 'type'], 'unique_product_rule');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};