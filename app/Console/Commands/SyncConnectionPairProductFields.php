<?php

namespace App\Console\Commands;

use App\Models\ConnectionPairProduct;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncConnectionPairProductFields extends Command
{
    protected $signature = 'connection-pair-products:sync-fields 
                          {--chunk=100 : Number of records to process at once}
                          {--dry-run : Run without making changes}';

    protected $description = 'Sync connection pair products with their corresponding product data (UPC, part number, condition)';

    public function handle()
    {
        $this->info('Starting connection pair product sync...');
        
        $chunkSize = $this->option('chunk');
        $isDryRun = $this->option('dry-run');
        $bar = null;
        
        try {
            // Get total count for progress bar
            $total = ConnectionPairProduct::count();
            $bar = $this->output->createProgressBar($total);
            $bar->start();
            
            $updated = 0;
            $errors = 0;

            ConnectionPairProduct::with('product')
                ->chunk($chunkSize, function ($connectionPairProducts) use (&$updated, &$errors, $bar, $isDryRun) {
                    DB::beginTransaction();
                    
                    try {
                        foreach ($connectionPairProducts as $cpp) {
                            if (!$cpp->product) {
                                if ($isDryRun) {
                                    Log::info("Would delete connection pair product (no matching product)", [
                                        'id' => $cpp->id,
                                    ]);
                                } else {
                                    $cpp->delete();
                                }
                                $errors++;
                                $bar->advance();
                                continue;
                            }

                            $updateData = [
                                'name' => $cpp->product->name,
                                'upc' => $cpp->product->upc,
                                'part_number' => $cpp->product->part_number,
                                'condition' => $cpp->product->condition,
                                'price' => $cpp->product->cost_price, // Base price before pricing rules
                                'stock' => $cpp->product->stock_quantity,
                            ];

                            // Log what would be updated
                            $changes = array_filter($updateData, function ($value, $key) use ($cpp) {
                                return $cpp->$key !== $value;
                            }, ARRAY_FILTER_USE_BOTH);

                            if (!empty($changes)) {
                                if ($isDryRun) {
                                    Log::info("Would update connection pair product", [
                                        'id' => $cpp->id,
                                        'changes' => $changes
                                    ]);
                                } else {
                                    $cpp->update($updateData);
                                    
                                    // Apply pricing rules to calculate final price
                                    $finalPrice = $cpp->calculateFinalPrice();
                                    $cpp->update(['final_price' => $finalPrice]);
                                }
                                $updated++;
                            }

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
                        throw $e;
                    }
                });

            $bar->finish();
            $this->newLine();

            if ($isDryRun) {
                $this->info("Dry run completed. Would have updated {$updated} records.");
            } else {
                $this->info("Sync completed. Updated {$updated} records.");
            }

            if ($errors > 0) {
                $this->warn("{$errors} errors encountered. Check the logs for details.");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            if ($bar) {
                $bar->finish();
                $this->newLine();
            }

            $this->error("Command failed: {$e->getMessage()}");
            Log::error("Command failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }
}