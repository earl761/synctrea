<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connection_pair_product', function (Blueprint $table) {
            // Add composite indexes for better filtering performance
            $table->index(['connection_pair_id', 'catalog_status'], 'idx_cpp_connection_status');
            $table->index(['connection_pair_id', 'price_override_type'], 'idx_cpp_connection_override');
        });
    }

    public function down(): void
    {
        Schema::table('connection_pair_product', function (Blueprint $table) {
            $table->dropIndex('idx_cpp_connection_status');
            $table->dropIndex('idx_cpp_connection_override');
        });
    }
}; 