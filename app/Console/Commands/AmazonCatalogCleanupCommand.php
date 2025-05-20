<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\AmazonFeed;
use App\Services\Api\AmazonApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AmazonCatalogCleanupCommand extends Command
{
    protected $signature = 'amazon:catalog-cleanup 
                          {--mode=retry : Mode to run (retry: retry failed deletions, bulk: bulk delete pending items)}
                          {--chunk=100 : Number of items to process per batch}
                          {--connection-pair= : Specific connection pair ID to process}';

    protected $description = 'Clean up Amazon catalog items by processing pending deletions and retrying failed ones';

    public function handle()
    {
        $mode = $this->option('mode');
        $chunkSize = (int)$this->option('chunk');
        $connectionPairId = $this->option('connection-pair');

        $this->info("Starting Amazon catalog cleanup in {$mode} mode");

        switch ($mode) {
            case 'retry':
                $this->retryFailedDeletions($chunkSize, $connectionPairId);
                break;
            case 'bulk':
                $this->processBulkDeletions($chunkSize, $connectionPairId);
                break;
            default:
                $this->error("Invalid mode specified: {$mode}");
                return 1;
        }

        return 0;
    }

    private function retryFailedDeletions(int $chunkSize, ?string $connectionPairId): void
    {
        $query = Product::where('in_catalog', 'deletion_failed');
        
        if ($connectionPairId) {
            $query->whereHas('connectionPair', function ($q) use ($connectionPairId) {
                $q->where('id', $connectionPairId);
            });
        }

        $total = $query->count();
        $this->info("Found {$total} failed deletions to retry");

        $query->chunkById($chunkSize, function ($products) {
            foreach ($products as $product) {
                try {
                    $this->info("Retrying deletion for product {$product->id}");
                    
                    $amazonClient = new AmazonApiClient($product->connectionPair);
                    $result = $amazonClient->deleteFromSellerCatalog($product);

                    if ($result) {
                        $product->update([
                            'in_catalog' => 'deleted',
                            'catalog_deletion_at' => now(),
                            'catalog_deletion_error' => null
                        ]);
                        $this->info("Successfully deleted product {$product->id}");
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to delete product {$product->id}: {$e->getMessage()}");
                    Log::error('Catalog deletion retry failed', [
                        'product_id' => $product->id,
                        'error' => $e->getMessage()
                    ]);
                    
                    $product->update([
                        'catalog_deletion_error' => $e->getMessage()
                    ]);
                }
            }
        });
    }

    private function processBulkDeletions(int $chunkSize, ?string $connectionPairId): void
    {
        $query = Product::where('in_catalog', 'pending_deletion');
        
        if ($connectionPairId) {
            $query->whereHas('connectionPair', function ($q) use ($connectionPairId) {
                $q->where('id', $connectionPairId);
            });
        }

        $total = $query->count();
        $this->info("Found {$total} pending deletions to process");

        $query->chunkById($chunkSize, function ($products) {
            try {
                // Group products by connection pair for efficient processing
                $groupedProducts = $products->groupBy('connection_pair_id');
                
                foreach ($groupedProducts as $connectionPairId => $productsGroup) {
                    $this->info("Processing deletion batch for connection pair {$connectionPairId}");
                    
                    $connectionPair = $productsGroup->first()->connectionPair;
                    $amazonClient = new AmazonApiClient($connectionPair);
                    
                    // Use bulk delete feed
                    $result = $amazonClient->deleteBulkFromSellerCatalog($productsGroup->all());
                    
                    // Create feed record
                    AmazonFeed::create([
                        'connection_pair_id' => $connectionPairId,
                        'feed_id' => $result['feed_id'],
                        'feed_type' => 'DELETE_CATALOG_ITEMS',
                        'feed_document_id' => $result['feed_document_id'],
                        'processing_status' => 'SUBMITTED'
                    ]);

                    // Update products status
                    foreach ($productsGroup as $product) {
                        $product->update([
                            'in_catalog' => 'deletion_in_progress',
                            'catalog_deletion_at' => now()
                        ]);
                    }
                    
                    $this->info("Submitted deletion feed for " . $productsGroup->count() . " products");
                }
            } catch (\Exception $e) {
                $this->error("Failed to process bulk deletion: {$e->getMessage()}");
                Log::error('Bulk catalog deletion failed', [
                    'error' => $e->getMessage(),
                    'connection_pair_id' => $connectionPairId ?? 'unknown'
                ]);
            }
        });
    }
}