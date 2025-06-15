<?php

namespace App\Console\Commands;

use App\Models\Destination;
use App\Services\Api\NeweggApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NeweggListCatalogCommand extends Command
{
    protected $signature = 'newegg:list-catalog 
                            {--destination-id= : The Newegg destination ID}
                            {--sku= : Specific SKU to retrieve (optional)}
                            {--limit=10 : Number of items to display (default: 10)}';

    protected $description = 'List items from Newegg catalog';

    public function handle(): int
    {
        try {
            $destinationId = $this->option('destination-id');
            $sku = $this->option('sku');
            $limit = (int) $this->option('limit');

            if (!$destinationId) {
                $this->error('Please provide a destination ID using --destination-id option');
                return 1;
            }

            // Get Newegg destination
            $destination = Destination::where('type', Destination::TYPE_NEWEGG)
                ->where('id', $destinationId)
                ->where('is_active', true)
                ->first();

            if (!$destination) {
                $this->error('Newegg destination not found or inactive');
                return 1;
            }

            $this->info("Connecting to Newegg API for destination: {$destination->name}");

            // Initialize API client
            $client = new NeweggApiClient($destination);
            $client->initialize();

            if ($sku) {
                // Get specific product by SKU
                $this->info("Retrieving product with SKU: {$sku}");
                $product = $client->getProduct($sku);
                
                if (empty($product)) {
                    $this->warn("No product found with SKU: {$sku}");
                    return 0;
                }

                $this->displayProduct($product);
            } else {
                // Get list of products
                $this->info("Retrieving catalog items (limit: {$limit})");
                $products = $client->getProducts();
                
                if (empty($products)) {
                    $this->warn('No products found in catalog');
                    return 0;
                }

                $this->info("Found " . count($products) . " products in catalog");
                
                // Display limited number of products
                $displayProducts = array_slice($products, 0, $limit);
                
                $this->table(
                    ['SKU', 'Name', 'Price', 'Inventory', 'Status', 'Condition'],
                    array_map(function ($product) {
                        return [
                            $product['SellerPartNumber'] ?? 'N/A',
                            substr($product['ItemName'] ?? 'N/A', 0, 50),
                            '$' . number_format($product['UnitPrice'] ?? 0, 2),
                            $product['Inventory'] ?? 'N/A',
                            $this->getStatusText($product['Status'] ?? 0),
                            $this->getConditionText($product['ItemCondition'] ?? 1)
                        ];
                    }, $displayProducts)
                );

                if (count($products) > $limit) {
                    $this->info("Showing {$limit} of " . count($products) . " total products. Use --limit option to show more.");
                }
            }

            $this->info('✅ Catalog listing completed successfully');
            return 0;

        } catch (\Exception $e) {
            Log::error('NeweggListCatalogCommand failed', [
                'destination_id' => $destinationId ?? null,
                'sku' => $sku ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->error('Failed to list catalog: ' . $e->getMessage());
            return 1;
        }
    }

    private function displayProduct(array $product): void
    {
        $this->info('Product Details:');
        $this->line('SKU: ' . ($product['SellerPartNumber'] ?? 'N/A'));
        $this->line('Name: ' . ($product['ItemName'] ?? 'N/A'));
        $this->line('Description: ' . substr($product['Description'] ?? 'N/A', 0, 100));
        $this->line('Price: $' . number_format($product['UnitPrice'] ?? 0, 2));
        $this->line('Inventory: ' . ($product['Inventory'] ?? 'N/A'));
        $this->line('Status: ' . $this->getStatusText($product['Status'] ?? 0));
        $this->line('Condition: ' . $this->getConditionText($product['ItemCondition'] ?? 1));
        $this->line('MPN: ' . ($product['ManufacturerPartNumber'] ?? 'N/A'));
        $this->line('UPC: ' . ($product['UPC'] ?? 'N/A'));
        $this->line('Currency: ' . ($product['Currency'] ?? 'N/A'));
        
        if (!empty($product['ItemImages'])) {
            $this->line('Images:');
            foreach ($product['ItemImages'] as $index => $image) {
                $this->line('  ' . ($index + 1) . '. ' . ($image['ImageUrl'] ?? 'N/A'));
            }
        }
    }

    private function getStatusText(int $status): string
    {
        return match($status) {
            0 => 'Inactive',
            1 => 'Active',
            2 => 'Discontinued',
            default => 'Unknown (' . $status . ')'
        };
    }

    private function getConditionText(int $condition): string
    {
        return match($condition) {
            1 => 'New',
            2 => 'Refurbished',
            3 => 'Used',
            default => 'Unknown (' . $condition . ')'
        };
    }
}