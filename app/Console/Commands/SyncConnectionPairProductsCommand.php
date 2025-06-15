<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\ConnectionPair;
use App\Models\ConnectionPairProduct;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncConnectionPairProductsCommand extends Command
{
    protected $signature = 'sync:connection-pair-products
        {--supplier=ingram_micro : Supplier type to sync products from}
        {--chunk=100 : Number of products to process per chunk}
        {--force : Force sync even if records exist}'
    ;

    protected $description = 'Sync products from a supplier to their corresponding connection pair products';

    public function handle(): int
    {
        $supplierType = $this->option('supplier');
        $chunkSize = (int) $this->option('chunk');
        $force = $this->option('force');

        try {
            // Get the supplier
            $supplier = Supplier::where('type', $supplierType)
                ->where('is_active', true)
                ->firstOrFail();

            // Get all active connection pairs for this supplier
            $connectionPairs = ConnectionPair::where('supplier_id', $supplier->id)
                ->where('is_active', true)
                ->get();

            if ($connectionPairs->isEmpty()) {
                $this->warn('No active connection pairs found for supplier: ' . $supplierType);
                return Command::FAILURE;
            }

            $this->info('Starting product sync for supplier: ' . $supplierType);
            $this->info('Found ' . $connectionPairs->count() . ' active connection pairs');

            // Get total products count for progress bar
            $totalProducts = Product::where('supplier_id', $supplier->id)->count();
            $progressBar = $this->output->createProgressBar($totalProducts);

            $totalCreated = 0;
            $totalSkipped = 0;
            $totalErrors = 0;

            // Process products in chunks
            Product::where('supplier_id', $supplier->id)
                ->chunkById($chunkSize, function ($products) use ($connectionPairs, $force, &$totalCreated, &$totalSkipped, &$totalErrors, $progressBar) {
                    foreach ($products as $product) {
                        foreach ($connectionPairs as $connectionPair) {
                            try {
                                DB::beginTransaction();

                                // Check if connection pair product already exists and is up to date
                                $existingProduct = ConnectionPairProduct::where('connection_pair_id', $connectionPair->id)
                                    ->where('product_id', $product->id)
                                    ->first();

                                if ($existingProduct && !$force) {
                                    // Skip only if all fields match
                                    if ($existingProduct->sku === $connectionPair->sku_prefix . $product->sku &&
                                        $existingProduct->name === $product->name &&
                                        $existingProduct->upc === $product->upc &&
                                        $existingProduct->condition === $product->condition &&
                                        $existingProduct->part_number === $product->part_number &&
                                        $existingProduct->price == $product->cost_price &&
                                        $existingProduct->fila_price == $product->retail_price &&
                                        $existingProduct->stock == $product->stock_quantity &&
                                        $existingProduct->weight == ($product->weight ?? 0)) {
                                        $totalSkipped++;
                                        continue;
                                    }
                                }

                                // Create or update connection pair product
                                $connectionPairProduct = ConnectionPairProduct::updateOrCreate(
                                    [
                                        'connection_pair_id' => $connectionPair->id,
                                        'product_id' => $product->id,
                                    ],
                                    [
                                        'sku' => $connectionPair->sku_prefix . $product->sku,
                                        'name' => $product->name,
                                        'upc' => $product->upc,
                                        'condition' => $product->condition,
                                        'part_number' => $product->part_number,
                                        'price' => $product->cost_price, // Base price before pricing rules
                                        'fila_price' => $product->retail_price,
                                        'stock' => $product->stock_quantity,
                                        'weight' => $product->weight ?? 0,
                                        'catalog_status' => ConnectionPairProduct::STATUS_DEFAULT,
                                        'sync_status' => 'pending',
                                        'price_override_type' => ConnectionPairProduct::PRICE_OVERRIDE_NONE
                                    ]
                                );

                                // Apply pricing rules to calculate final price
                                $finalPrice = $connectionPairProduct->calculateFinalPrice();
                                $connectionPairProduct->update(['final_price' => $finalPrice]);

                                DB::commit();
                                $totalCreated++;

                            } catch (\Exception $e) {
                                DB::rollBack();
                                $totalErrors++;
                                Log::error('Failed to sync product to connection pair', [
                                    'product_id' => $product->id,
                                    'connection_pair_id' => $connectionPair->id,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                        $progressBar->advance();
                    }
                });

            $progressBar->finish();
            $this->newLine(2);

            // Delete orphaned connection pair products (no matching product)
            $orphaned = ConnectionPairProduct::whereIn('connection_pair_id', $connectionPairs->pluck('id'))
                ->whereDoesntHave('product')
                ->get();

            $deletedCount = 0;
            foreach ($orphaned as $cpp) {
                $cpp->delete();
                $deletedCount++;
            }
            $this->info("Deleted orphaned connection pair products: {$deletedCount}");

            $this->info('Sync completed:');
            $this->info("Created/Updated: {$totalCreated}");
            $this->info("Skipped: {$totalSkipped}");
            $this->info("Errors: {$totalErrors}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to sync products: ' . $e->getMessage());
            Log::error('Failed to sync products to connection pairs', [
                'supplier_type' => $supplierType,
                'error' => $e->getMessage()
            ]);
            return Command::FAILURE;
        }
    }
}