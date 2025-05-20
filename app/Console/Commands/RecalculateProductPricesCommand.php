<?php

namespace App\Console\Commands;

use App\Models\ConnectionPairProduct;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RecalculateProductPricesCommand extends Command
{
    protected $signature = 'products:recalculate-prices {connection_pair_id? : Optional connection pair ID}';
    protected $description = 'Recalculate prices for all products based on pricing rules';

    public function handle()
    {
        $query = ConnectionPairProduct::query();

        if ($connectionPairId = $this->argument('connection_pair_id')) {
            $query->where('connection_pair_id', $connectionPairId);
        }

        $total = $query->count();
        $this->info("Recalculating prices for {$total} products...");
        $bar = $this->output->createProgressBar($total);

        $query->chunk(100, function ($products) use ($bar) {
            foreach ($products as $product) {
                try {
                    $oldPrice = $product->final_price;
                    $product->final_price = $product->calculateFinalPrice();
                    
                    if ($product->final_price !== $oldPrice) {
                        $product->save();
                        
                        Log::info('Product price updated', [
                            'product_id' => $product->id,
                            'old_price' => $oldPrice,
                            'new_price' => $product->final_price
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Error updating product price', [
                        'product_id' => $product->id,
                        'error' => $e->getMessage()
                    ]);
                }
                
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Price recalculation completed!');
    }
} 