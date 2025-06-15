<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('connection_pair_product', function (Blueprint $table) {
            // Add indexes for commonly searched and sorted columns
            $table->index('sku', 'idx_cpp_sku');
            $table->index('upc', 'idx_cpp_upc');
            $table->index('part_number', 'idx_cpp_part_number');
            $table->index('price', 'idx_cpp_price');
            $table->index('final_price', 'idx_cpp_final_price');
            $table->index('stock', 'idx_cpp_stock');
            $table->index('weight', 'idx_cpp_weight');
            $table->index('condition', 'idx_cpp_condition');
            
            // Composite indexes for common filter combinations
            $table->index(['connection_pair_id', 'sku'], 'idx_cpp_pair_sku');
            $table->index(['connection_pair_id', 'upc'], 'idx_cpp_pair_upc');
            $table->index(['connection_pair_id', 'price'], 'idx_cpp_pair_price');
            $table->index(['connection_pair_id', 'stock'], 'idx_cpp_pair_stock');
            $table->index(['connection_pair_id', 'condition'], 'idx_cpp_pair_condition');
        });
        
        // Add indexes to products table for joined queries
        Schema::table('products', function (Blueprint $table) {
            // Check if indexes don't already exist
            if (!Schema::hasIndex('products', 'idx_products_name')) {
                $table->index('name', 'idx_products_name');
            }
            if (!Schema::hasIndex('products', 'idx_products_brand')) {
                $table->index('brand', 'idx_products_brand');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('connection_pair_product', function (Blueprint $table) {
            $table->dropIndex('idx_cpp_pair_condition');
            $table->dropIndex('idx_cpp_pair_stock');
            $table->dropIndex('idx_cpp_pair_price');
            $table->dropIndex('idx_cpp_pair_upc');
            $table->dropIndex('idx_cpp_pair_sku');
            $table->dropIndex('idx_cpp_condition');
            $table->dropIndex('idx_cpp_weight');
            $table->dropIndex('idx_cpp_stock');
            $table->dropIndex('idx_cpp_final_price');
            $table->dropIndex('idx_cpp_price');
            $table->dropIndex('idx_cpp_part_number');
            $table->dropIndex('idx_cpp_upc');
            $table->dropIndex('idx_cpp_sku');
        });
        
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasIndex('products', 'idx_products_brand')) {
                $table->dropIndex('idx_products_brand');
            }
            if (Schema::hasIndex('products', 'idx_products_name')) {
                $table->dropIndex('idx_products_name');
            }
        });
    }
};