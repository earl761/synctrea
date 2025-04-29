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
            // Supplier is already retrieved above

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

            do {
                $params['pageNumber'] = $pageNumber;
                $result = $client->getCatalog($params);

                if (!$progressBar && isset($result['recordsFound'])) {
                    $progressBar = $this->output->createProgressBar($result['recordsFound']);
                }

                if (empty($result['catalog'])) {
                    break;
                }

                DB::beginTransaction();
                try {
                    foreach ($result['catalog'] as $item) {
                        $product = Product::updateOrCreate(
                            [
                                'supplier_id' => $supplier->id,
                                'sku' => $item['ingramPartNumber'],
                            ],
                            [
                                'name' => $item['description'],
                                'type' => $item['type'],
                                'part_number' => $item['vendorPartNumber'],
                                'authorizedToPurchase' => $item['authorizedToPurchase'] === 'True',
                                'cost_price' => 0,
                                'retail_price' => 0,
                                'quantity' => 0,
                                'description' => $item['extraDescription'] ?? '',
                                'category' => $item['category'] ?? '',
                                'subcategory' => $item['subCategory'] ?? '',
                                'brand' => $item['vendorName'] ?? '',
                                'upc' => $item['upcCode'] ?? '',
                                'is_discontinued' => $item['discontinued'] === 'True',
                                'is_direct_ship' => $item['directShip'] === 'True',
                                'has_warranty' => $item['hasWarranty'] === 'True',
                                'metadata' => $item,
                                'synced_at' => now(),
                            ]
                        );

                        $totalProcessed++;
                        if ($product->wasRecentlyCreated) {
                            $totalCreated++;
                        } else {
                            $totalUpdated++;
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

                $pageNumber++;
            } while (!empty($result['catalog']));

            if ($progressBar) {
                $progressBar->finish();
                $this->newLine();
            }

            $syncLog->update([
                'status' => 'completed',
                'completed_at' => now(),
                'metadata' => [
                    'total_processed' => $totalProcessed,
                    'total_created' => $totalCreated,
                    'total_updated' => $totalUpdated,
                ],
            ]);

            $this->info(sprintf(
                'Sync completed. Processed %d products (%d created, %d updated)',
                $totalProcessed,
                $totalCreated,
                $totalUpdated
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