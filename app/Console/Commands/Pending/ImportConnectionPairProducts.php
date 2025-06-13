<?php

namespace App\Console\Commands;

use App\Models\ConnectionPair;
use App\Models\ConnectionPairProduct;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use League\Csv\Statement;

class ImportConnectionPairProducts extends Command
{
    protected $signature = 'connection-pair-products:import 
                          {connection_pair_id : ID of the connection pair}
                          {csv : Path to the CSV file}
                          {--chunk=100 : Number of records to process at once}
                          {--dry-run : Run without making changes}';

    protected $description = 'Import connection pair products from a CSV file';

    public function handle()
    {
        $connectionPairId = $this->argument('connection_pair_id');
        $csvPath = $this->argument('csv');
        $chunkSize = $this->option('chunk');
        $isDryRun = $this->option('dry-run');

        // Validate connection pair exists
        $connectionPair = ConnectionPair::find($connectionPairId);
        if (!$connectionPair) {
            $this->error("Connection pair not found with ID: {$connectionPairId}");
            return Command::FAILURE;
        }

        // Validate CSV file exists
        if (!file_exists($csvPath)) {
            $this->error("CSV file not found: {$csvPath}");
            return Command::FAILURE;
        }

        try {
            $csv = Reader::createFromPath($csvPath, 'r');
            $csv->setHeaderOffset(0);

            // Validate required columns
            $headers = $csv->getHeader();
            $requiredColumns = ['sku', 'name', 'upc', 'part_number', 'condition', 'price', 'stock'];
            $missingColumns = array_diff($requiredColumns, $headers);

            if (!empty($missingColumns)) {
                $this->error("Missing required columns: " . implode(', ', $missingColumns));
                return Command::FAILURE;
            }

            $records = Statement::create()->process($csv);
            $total = iterator_count($records);
            $bar = $this->output->createProgressBar($total);
            $bar->start();

            $processed = 0;
            $created = 0;
            $updated = 0;
            $errors = 0;

            // Process in chunks
            foreach (array_chunk(iterator_to_array($records), $chunkSize) as $chunk) {
                DB::beginTransaction();
                
                try {
                    foreach ($chunk as $record) {
                        // Find or create product
                        $product = Product::where('supplier_id', $connectionPair->supplier_id)
                            ->where('sku', $record['sku'])
                            ->first();

                        if (!$product) {
                            if ($isDryRun) {
                                Log::info("Would create new product", ['sku' => $record['sku']]);
                            } else {
                                $product = Product::create([
                                    'supplier_id' => $connectionPair->supplier_id,
                                    'sku' => $record['sku'],
                                    'name' => $record['name'],
                                    'upc' => $record['upc'],
                                    'part_number' => $record['part_number'],
                                    'condition' => $record['condition'],
                                    'cost_price' => $record['price'],
                                    'stock_quantity' => $record['stock'],
                                ]);
                            }
                            $created++;
                        }

                        if ($product) {
                            // Create or update connection pair product
                            $cpp = ConnectionPairProduct::firstOrNew([
                                'connection_pair_id' => $connectionPairId,
                                'product_id' => $product->id,
                            ]);

                            $updateData = [
                                'sku' => $record['sku'],
                                'name' => $record['name'],
                                'upc' => $record['upc'],
                                'part_number' => $record['part_number'],
                                'condition' => $record['condition'],
                                'price' => $record['price'],
                                'stock' => $record['stock'],
                                'catalog_status' => ConnectionPairProduct::STATUS_DEFAULT,
                            ];

                            if ($isDryRun) {
                                Log::info($cpp->exists ? "Would update" : "Would create", [
                                    'sku' => $record['sku'],
                                    'data' => $updateData
                                ]);
                            } else {
                                $cpp->fill($updateData);
                                $cpp->save();
                            }

                            $cpp->exists ? $updated++ : $created++;
                        }

                        $processed++;
                        $bar->advance();
                    }

                    if (!$isDryRun) {
                        DB::commit();
                    }
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("Error processing chunk", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $errors++;
                }
            }

            $bar->finish();
            $this->newLine();

            if ($isDryRun) {
                $this->info("Dry run completed. Would have processed {$processed} records:");
                $this->info("- Would create: {$created}");
                $this->info("- Would update: {$updated}");
            } else {
                $this->info("Import completed. Processed {$processed} records:");
                $this->info("- Created: {$created}");
                $this->info("- Updated: {$updated}");
            }

            if ($errors > 0) {
                $this->warn("{$errors} errors encountered. Check the logs for details.");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Command failed: {$e->getMessage()}");
            Log::error("Import failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }
} 