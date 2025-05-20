<?php

namespace App\Console\Commands;

use App\Models\ConnectionPair;
use App\Models\Product;
use App\Models\SyncLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ManageConnectionPairProductsCommand extends Command
{
    protected $signature = 'connection-pair:manage-products
                            {connection_pair_id : The ID of the connection pair}
                            {--action=queue : The action to perform (queue/catalog/remove)}
                            {--product-ids=* : Specific product IDs to process}
                            {--all : Process all products in the connection pair}
                            {--status= : Filter by current catalog status}';

    protected $description = 'Manage products in a connection pair';

    public function handle(): int
    {
        $connectionPairId = $this->argument('connection_pair_id');
        $action = $this->option('action');
        $productIds = $this->option('product-ids');
        $processAll = $this->option('all');
        $status = $this->option('status');

        try {
            // Get the connection pair
            $connectionPair = ConnectionPair::with(['destination', 'products'])->findOrFail($connectionPairId);
            
            $this->info("Managing products for connection pair: {$connectionPair->id}");
            $this->info("Destination: {$connectionPair->destination->name}");
            $this->info("Action: {$action}");

            // Build the query
            $query = $connectionPair->products();
            
            if (!empty($productIds)) {
                $query->whereIn('products.id', $productIds);
            }
            
            if ($status) {
                $query->wherePivot('catalog_status', $status);
            }

            // Get the products
            $products = $query->get();

            if ($products->isEmpty()) {
                $this->warn('No products found matching the criteria.');
                return 0;
            }

            $this->info("Found {$products->count()} products to process.");

            // Process each product
            $bar = $this->output->createProgressBar($products->count());
            $bar->start();

            foreach ($products as $product) {
                try {
                    switch ($action) {
                        case 'queue':
                            $this->addToQueue($connectionPair, $product);
                            break;
                        case 'catalog':
                            $this->addToCatalog($connectionPair, $product);
                            break;
                        case 'remove':
                            $this->removeFromCatalog($connectionPair, $product);
                            break;
                        default:
                            throw new \Exception("Invalid action: {$action}");
                    }

                    $bar->advance();
                } catch (\Exception $e) {
                    $this->error("\nError processing product {$product->sku}: {$e->getMessage()}");
                    Log::error("Error managing product {$product->sku}", [
                        'connection_pair_id' => $connectionPair->id,
                        'product_id' => $product->id,
                        'action' => $action,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $bar->finish();
            $this->newLine(2);
            $this->info('Product management completed successfully.');

        } catch (\Exception $e) {
            $this->error("Fatal error: {$e->getMessage()}");
            Log::error("Fatal error in product management", [
                'connection_pair_id' => $connectionPairId,
                'error' => $e->getMessage()
            ]);
            return 1;
        }

        return 0;
    }

    private function addToQueue(ConnectionPair $connectionPair, Product $product): void
    {
        $product->connectionPairs()->updateExistingPivot(
            $connectionPair->id,
            ['catalog_status' => 'queue']
        );

        SyncLog::create([
            'connection_pair_id' => $connectionPair->id,
            'product_id' => $product->id,
            'status' => 'success',
            'message' => 'Product added to queue'
        ]);
    }

    private function addToCatalog(ConnectionPair $connectionPair, Product $product): void
    {
        $product->connectionPairs()->updateExistingPivot(
            $connectionPair->id,
            ['catalog_status' => 'catalog']
        );

        SyncLog::create([
            'connection_pair_id' => $connectionPair->id,
            'product_id' => $product->id,
            'status' => 'success',
            'message' => 'Product added to catalog'
        ]);
    }

    private function removeFromCatalog(ConnectionPair $connectionPair, Product $product): void
    {
        $product->connectionPairs()->updateExistingPivot(
            $connectionPair->id,
            ['catalog_status' => 'default']
        );

        SyncLog::create([
            'connection_pair_id' => $connectionPair->id,
            'product_id' => $product->id,
            'status' => 'success',
            'message' => 'Product removed from catalog'
        ]);
    }
} 