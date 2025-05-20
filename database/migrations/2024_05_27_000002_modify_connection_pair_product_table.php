<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connection_pair_product', function (Blueprint $table) {
            // Add new fields for product details
            $table->string('sku')->after('product_id');
            $table->string('name')->after('sku');
            $table->decimal('price', 10, 2)->after('name');
            $table->integer('stock')->after('price');
            
            // Add indexes
            $table->index('sku');
            $table->index(['connection_pair_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::table('connection_pair_product', function (Blueprint $table) {
            $table->dropColumn(['sku', 'name', 'price', 'stock']);
            $table->dropIndex(['sku']);
            $table->dropIndex(['connection_pair_id', 'sku']);
        });
    }
}; 