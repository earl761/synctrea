<?php

namespace App\Console\Commands;

use App\Models\ConnectionPairProduct;
use App\Services\SyncStatusManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupOrphanedConnectionPairProductsCommand extends Command
{
    protected $signature = 'cleanup:orphaned-connection-pair-products
        {--dry-run : Show what would be deleted without actually deleting}
        {--force : Force cleanup without confirmation}'
    ;

    protected $description = 'Clean up connection pair products that no longer have corresponding active products';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        try {
            $this->info('Scanning for orphaned connection pair products...');

            // Find connection pair products where the product_id doesn't exist in products table
            $orphanedNoProduct = ConnectionPairProduct::whereNotExists(function($query) {
                $query->select(DB::raw(1))
                      ->from('products')
                      ->whereRaw('products.id = connection_pair_product.product_id')
                      ->whereNull('products.deleted_at');
            })->get();

            // Find connection pair products linked to soft-deleted products
            $orphanedSoftDeleted = ConnectionPairProduct::whereExists(function($query) {
                $query->select(DB::raw(1))
                      ->from('products')
                      ->whereRaw('products.id = connection_pair_product.product_id')
                      ->whereNotNull('products.deleted_at');
            })->get();

            $totalOrphaned = $orphanedNoProduct->count() + $orphanedSoftDeleted->count();

            if ($totalOrphaned === 0) {
                $this->info('No orphaned connection pair products found.');
                return Command::SUCCESS;
            }

            $this->info("Found {$totalOrphaned} orphaned connection pair products:");
            $this->info("- {$orphanedNoProduct->count()} with no corresponding product");
            $this->info("- {$orphanedSoftDeleted->count()} linked to soft-deleted products");

            if ($dryRun) {
                $this->warn('DRY RUN MODE - No changes will be made');
                
                if ($orphanedNoProduct->count() > 0) {
                    $this->info('\nConnection pair products with no corresponding product:');
                    foreach ($orphanedNoProduct->take(10) as $cpp) {
                        $this->line("- ID: {$cpp->id}, SKU: {$cpp->sku}, Connection Pair: {$cpp->connection_pair_id}");
                    }
                    if ($orphanedNoProduct->count() > 10) {
                        $this->line('... and ' . ($orphanedNoProduct->count() - 10) . ' more');
                    }
                }

                if ($orphanedSoftDeleted->count() > 0) {
                    $this->info('\nConnection pair products linked to soft-deleted products:');
                    foreach ($orphanedSoftDeleted->take(10) as $cpp) {
                        $this->line("- ID: {$cpp->id}, SKU: {$cpp->sku}, Product ID: {$cpp->product_id}");
                    }
                    if ($orphanedSoftDeleted->count() > 10) {
                        $this->line('... and ' . ($orphanedSoftDeleted->count() - 10) . ' more');
                    }
                }

                return Command::SUCCESS;
            }

            if (!$force && !$this->confirm("Are you sure you want to delete {$totalOrphaned} orphaned connection pair products?")) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }

            $syncStatusManager = app(SyncStatusManager::class);
            $deletedCount = 0;
            $catalogDeletionCount = 0;

            DB::beginTransaction();

            try {
                // Handle orphaned products with no corresponding product
                foreach ($orphanedNoProduct as $cpp) {
                    if ($cpp->catalog_status === ConnectionPairProduct::STATUS_IN_CATALOG) {
                        // Mark for catalog deletion first
                        $syncStatusManager->updateCatalogStatus(
                            $cpp, 
                            SyncStatusManager::CATALOG_STATUS_PENDING_DELETION,
                            'Product no longer exists'
                        );
                        $syncStatusManager->markAsPending($cpp, 'Product no longer exists - needs catalog removal');
                        $catalogDeletionCount++;
                        
                        Log::info('Marked orphaned connection pair product for catalog deletion', [
                            'connection_pair_product_id' => $cpp->id,
                            'reason' => 'Product no longer exists'
                        ]);
                    } else {
                        // Safe to delete immediately
                        $cpp->delete();
                        $deletedCount++;
                        
                        Log::info('Deleted orphaned connection pair product', [
                            'connection_pair_product_id' => $cpp->id,
                            'reason' => 'Product no longer exists'
                        ]);
                    }
                }

                // Handle orphaned products linked to soft-deleted products
                foreach ($orphanedSoftDeleted as $cpp) {
                    if ($cpp->catalog_status === ConnectionPairProduct::STATUS_IN_CATALOG) {
                        // Mark for catalog deletion first
                        $syncStatusManager->updateCatalogStatus(
                            $cpp, 
                            SyncStatusManager::CATALOG_STATUS_PENDING_DELETION,
                            'Product was deleted'
                        );
                        $syncStatusManager->markAsPending($cpp, 'Product was deleted - needs catalog removal');
                        $catalogDeletionCount++;
                        
                        Log::info('Marked orphaned connection pair product for catalog deletion', [
                            'connection_pair_product_id' => $cpp->id,
                            'product_id' => $cpp->product_id,
                            'reason' => 'Product was soft-deleted'
                        ]);
                    } else {
                        // Safe to delete immediately
                        $cpp->delete();
                        $deletedCount++;
                        
                        Log::info('Deleted orphaned connection pair product', [
                            'connection_pair_product_id' => $cpp->id,
                            'product_id' => $cpp->product_id,
                            'reason' => 'Product was soft-deleted'
                        ]);
                    }
                }

                DB::commit();

                $this->info('Cleanup completed successfully:');
                $this->info("- Deleted immediately: {$deletedCount}");
                $this->info("- Marked for catalog deletion: {$catalogDeletionCount}");
                $this->info("- Total processed: {$totalOrphaned}");

                if ($catalogDeletionCount > 0) {
                    $this->warn('Note: Some products were marked for catalog deletion and will be removed from external catalogs during the next sync.');
                }

                return Command::SUCCESS;

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            $this->error('Failed to cleanup orphaned connection pair products: ' . $e->getMessage());
            Log::error('Failed to cleanup orphaned connection pair products', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}