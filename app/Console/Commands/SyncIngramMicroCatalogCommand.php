<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\SyncLog;
use App\Services\Api\IngramMicroApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncIngramMicroCatalogCommand extends Command
{
    protected $signature = 'ingram:sync-catalog
        {--chunk=100 : Number of items per page to process}
        {--vendor= : Filter by vendor name}
        {--keyword= : Search by keyword}
        {--sku= : Filter by vendor part number}
        {--force : Force sync even if there was a recent successful sync}'
    ;

    protected $description = 'Sync products from Ingram Micro catalog';

    public function handle(): int
    { 
        // Get the supplier first to ensure it exists before creating the sync log
        $supplier = Supplier::where('type', 'ingram_micro')
            ->where('is_active', true)
            ->firstOrFail();

        $syncLog = new SyncLog([
            'supplier_id' => $supplier->id,
            'type' => 'ingram_micro_catalog',
            'status' => 'running',
            'started_at' => now(),
        ]);
        $syncLog->save();

        try {
            // Check for recent successful sync unless forced
            if (!$this->option('force')) {
                $recentSync = SyncLog::where('type', 'ingram_micro_catalog')
                    ->where('status', 'completed')
                    ->where('created_at', '>=', now()->subHours(12))
                    ->exists();

                if ($recentSync) {
                    $this->warn('A successful sync was performed in the last 12 hours. Use --force to override.');
                    return Command::FAILURE;
                }
            }

            $client = new IngramMicroApiClient($supplier);
            $client->initialize();

            $pageSize = min((int) $this->option('chunk'), 100);
            $pageNumber = 1;
            $totalProcessed = 0;
            $totalUpdated = 0;
            $totalCreated = 0;
            $processedSkus = []; // Track SKUs we've seen in the response

            $params = [
                'pageSize' => $pageSize,
            ];

            if ($vendor = $this->option('vendor')) {
                $params['vendor'] = $vendor;
            }

            if ($keyword = $this->option('keyword')) {
                $params['keyword'] = $keyword;
            }

            if ($sku = $this->option('sku')) {
                $params['ingramPartNumber'] = $sku;
            }

            $this->info('Starting Ingram Micro catalog sync...');
            $progressBar = null;
            $totalRecords = 0; // Initialize total records counter
            $failedAttempts = 0; // Initialize failed attempts counter
            $maxRetries = 3; // Set maximum retry attempts

            do {
                $params['pageNumber'] = $pageNumber;
                $result = $client->getCatalog($params);

                if (!$progressBar && isset($result['recordsFound'])) {
                    $totalRecords = $result['recordsFound']; // Set total records from API response
                    $this->info("\nTotal records found: {$totalRecords}");
                    $progressBar = $this->output->createProgressBar($totalRecords);
                }

                // Handle empty responses
                if (empty($result['catalog'])) {
                    $failedAttempts++;
                    if ($failedAttempts >= $maxRetries) {
                        $this->warn("\nNo more products found after {$totalProcessed} items.");
                        break;
                    }
                    // Add a delay before retrying
                    sleep(2);
                    $this->warn("Empty response received, retrying... (Attempt {$failedAttempts} of {$maxRetries})");
                    continue;
                }

                // Reset failed attempts counter on successful response
                $failedAttempts = 0;

                DB::beginTransaction();
                try {
                    foreach ($result['catalog'] as $item) {
                        $sku = (string) $item['ingramPartNumber'];
                        
                        // Check if product exists before attempting to create/update
                        $existingProduct = Product::where('supplier_id', $supplier->id)
                            ->whereRaw('BINARY sku = ?', [$sku])
                            ->first();
                            
                        // Track processed SKUs
                        $processedSkus[] = $sku;

                        $updateData = [
                            'name' => $item['description'],
                            'type' => $item['type'],
                            'part_number' => $item['vendorPartNumber'],
                            'authorizedToPurchase' => $item['authorizedToPurchase'] === 'True',
                            'cost_price' => 0,
                            'retail_price' => 0,
                            'stock_quantity' => 0,
                            'description' => $item['extraDescription'] ?? '',
                            'category' => $item['category'] ?? '',
                            'subcategory' => $item['subCategory'] ?? '',
                            'brand' => $item['vendorName'] ?? '',
                            'upc' => $item['upcCode'] ?? '',
                            'metadata' => $item,
                        ];

                        try {
                            if ($existingProduct) {
                                $existingProduct->update($updateData);
                                $totalUpdated++;
                            } else {
                                Product::create([
                                    'supplier_id' => $supplier->id,
                                    'sku' => $sku,
                                    ...$updateData
                                ]);
                                $totalCreated++;
                            }
                            $totalProcessed++;
                        } catch (\Exception $e) {
                            $this->error("Failed to process product with SKU: {$sku}. Error: {$e->getMessage()}");
                        }
                        
                        if ($progressBar) {
                            $progressBar->advance();
                        }


                    }
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }

                // Check if we've processed all records
                if ($totalRecords > 0 && $totalProcessed >= $totalRecords) {
                    $this->info("\nProcessed all {$totalRecords} records successfully.");
                    break;
                }

                // Check if the API indicates completion
                if ($result['isComplete'] ?? false) {
                    $this->info("\nAPI indicates no more records to process.");
                    break;
                }

                $pageNumber++;
                $this->info("\nFetching page {$pageNumber}...");
                
                // Add delay between pages
                sleep(2); // 2 seconds delay between pages
            } while (true); // Loop will break when isComplete is true or no more items

            if ($progressBar) {
                $progressBar->finish();
                $this->newLine();
            }

            // Delete products that weren't in the response
            $totalDeleted = 0;
            if (!empty($processedSkus)) {
                DB::beginTransaction();
                try {
                    // Find and delete products not in the response
                    $totalDeleted = Product::where('supplier_id', $supplier->id)
                        ->whereNotIn('sku', $processedSkus)
                        ->delete();
                    
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }

            $syncLog->update([
                'status' => 'completed',
                'completed_at' => now(),
                'metadata' => [
                    'total_processed' => $totalProcessed,
                    'total_created' => $totalCreated,
                    'total_updated' => $totalUpdated,
                    'total_deleted' => $totalDeleted,
                ],
            ]);

            $this->info(sprintf(
                'Sync completed. Processed %d products (%d created, %d updated, %d deleted)',
                $totalProcessed,
                $totalCreated,
                $totalUpdated,
                $totalDeleted
            ));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Ingram Micro catalog sync failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            $syncLog->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error' => $e->getMessage(),
            ]);

            $this->error('Sync failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}