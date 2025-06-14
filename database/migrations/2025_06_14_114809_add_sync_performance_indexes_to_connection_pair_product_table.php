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
            // Add missing sync columns first
            if (!Schema::hasColumn('connection_pair_product', 'sync_status')) {
                $table->string('sync_status')->default('pending')->after('catalog_status');
            }
            
            if (!Schema::hasColumn('connection_pair_product', 'last_sync_attempt')) {
                $table->timestamp('last_sync_attempt')->nullable()->after('last_synced_at');
            }
        });
        
        // Add indexes in a separate schema call to avoid conflicts
        Schema::table('connection_pair_product', function (Blueprint $table) {
            // Index for sync status queries
            $table->index('sync_status', 'idx_connection_pair_product_sync_status');
            
            // Index for catalog status queries
            $table->index('catalog_status', 'idx_connection_pair_product_catalog_status');
            
            // Index for last sync attempt queries
            $table->index('last_sync_attempt', 'idx_connection_pair_product_last_sync_attempt');
            
            // Index for last synced at queries
            $table->index('last_synced_at', 'idx_connection_pair_product_last_synced_at');
            
            // Composite index for sync operations (most common query pattern)
            $table->index(['sync_status', 'catalog_status'], 'idx_connection_pair_product_sync_composite');
            
            // Composite index for connection pair and sync status
            $table->index(['connection_pair_id', 'sync_status'], 'idx_connection_pair_product_pair_sync');
            
            // Index for failed sync retries
            $table->index(['sync_status', 'last_sync_attempt'], 'idx_connection_pair_product_retry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('connection_pair_product', function (Blueprint $table) {
            // Drop indexes in reverse order
            $table->dropIndex('idx_connection_pair_product_retry');
            $table->dropIndex('idx_connection_pair_product_pair_sync');
            $table->dropIndex('idx_connection_pair_product_sync_composite');
            $table->dropIndex('idx_connection_pair_product_last_synced_at');
            $table->dropIndex('idx_connection_pair_product_last_sync_attempt');
            $table->dropIndex('idx_connection_pair_product_catalog_status');
            $table->dropIndex('idx_connection_pair_product_sync_status');
        });
        
        Schema::table('connection_pair_product', function (Blueprint $table) {
            // Drop the columns we added
            if (Schema::hasColumn('connection_pair_product', 'last_sync_attempt')) {
                $table->dropColumn('last_sync_attempt');
            }
            
            if (Schema::hasColumn('connection_pair_product', 'sync_status')) {
                $table->dropColumn('sync_status');
            }
        });
    }
};
