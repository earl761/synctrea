<?php

namespace App\Console\Commands;

use App\Models\ConnectionPair;
use App\Models\Product;
use App\Services\Api\AmazonApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestAmazonIntegrationCommand extends Command
{
    protected $signature = 'amazon:test
                            {--connection-pair-id= : The ID of the connection pair to test}
                            {--sandbox : Run in sandbox mode}
                            {--add-to-catalog : Add the product to seller catalog}';

    protected $description = 'Test Amazon SP-API integration';

    public function handle()
    {
        try {
            $connectionPairId = $this->option('connection-pair-id');
            $sandbox = $this->option('sandbox');
            $addToCatalog = $this->option('add-to-catalog');

            if (!$connectionPairId) {
                $this->error("Please provide a connection pair ID");
                return 1;
            }

            // Get the connection pair with its products
            $connectionPair = ConnectionPair::with(['destination', 'products'])
                ->where('id', $connectionPairId)
                ->first();

            if (!$connectionPair) {
                $this->error("No connection pair found with ID: {$connectionPairId}");
                return 1;
            }

            if ($connectionPair->destination->type !== 'amazon') {
                $this->error("Connection pair does not reference an Amazon destination");
                return 1;
            }

            $this->info("Testing Amazon SP-API integration for connection pair: {$connectionPairId}");
            $this->info("Destination: {$connectionPair->destination->name}");
            $this->info("Region: {$connectionPair->destination->region}");
            
            // Log database information
            $this->info("\nDatabase Information:");
            $this->info("Connection Pair ID: {$connectionPair->id}");
            $this->info("Connection Pair Settings: " . json_encode($connectionPair->settings, JSON_PRETTY_PRINT));
            
            $this->info("\nDestination Details:");
            $this->info("ID: {$connectionPair->destination->id}");
            $this->info("Name: {$connectionPair->destination->name}");
            $this->info("Type: {$connectionPair->destination->type}");
            $this->info("Region: {$connectionPair->destination->region}");
            $this->info("API Key: {$connectionPair->destination->api_key}");
            $this->info("API Secret: {$connectionPair->destination->api_secret}");
            $this->info("API Endpoint: {$connectionPair->destination->api_endpoint}");
            $this->info("Credentials: " . json_encode($connectionPair->destination->credentials, JSON_PRETTY_PRINT));
            $this->info("Settings: " . json_encode($connectionPair->destination->settings, JSON_PRETTY_PRINT));
            $this->info("Is Active: " . ($connectionPair->destination->is_active ? 'Yes' : 'No'));
            
            if ($sandbox) {
                $this->info("\nRunning in sandbox mode");
                // Update sandbox setting
                $settings = $connectionPair->settings ?? [];
                $settings['sandbox'] = true;
                $connectionPair->settings = $settings;
                $connectionPair->save();
            }

            // Initialize the client
            $client = new AmazonApiClient($connectionPair);

            // Test authentication with a simple catalog search
            $this->info("\nTesting authentication with a simple catalog search...");
            
            try {
                // Use static UPC that we know works
                $upc = '196105142421';
                $response = $client->searchCatalogItemByUpc($upc);
                
                if ($response) {
                    $this->info("✅ Authentication successful!");
                    $this->info("Response received from Amazon SP-API");
                    $this->line(json_encode($response, JSON_PRETTY_PRINT));

                    // Check if we have items in the response
                    if (isset($response['items']) && !empty($response['items'])) {
                        $this->info("\n✅ Product found in Amazon catalog!");
                        
                        // Get catalog item data
                        $catalogItem = $response['items'][0];
                        $catalogSummary = $catalogItem['summaries'][0];
                        
                        $this->info("\nCatalog Item Details:");
                        $this->info("ASIN: {$catalogItem['asin']}");
                        $this->info("Brand: {$catalogSummary['brand']}");
                        $this->info("Name: {$catalogSummary['itemName']}");
                        $this->info("Manufacturer: {$catalogSummary['manufacturer']}");
                        $this->info("Model Number: {$catalogSummary['modelNumber']}");
                        $this->info("Part Number: {$catalogSummary['partNumber']}");
                        
                        // If product exists in catalog, proceed with adding to seller catalog
                        if ($addToCatalog) {
                            $this->info("\nProceeding with adding to seller catalog...");
                            
                            // Create a test product model instance
                            $testProduct = new Product([
                                'sku' => 'TEST-' . $catalogItem['asin'],
                                'upc' => $upc,
                                'name' => $catalogSummary['itemName'],
                                'brand' => $catalogSummary['brand'],
                                'manufacturer' => $catalogSummary['manufacturer'],
                                'description' => $catalogSummary['itemName'],
                                'cost_price' => 29.99,
                                'retail_price' => 39.99,
                                'stock_quantity' => 100,
                                'status' => 'active',
                                'catalog_data' => $response,
                            ]);

                            // Set the pivot relationship
                            $testProduct->pivot = (object)[
                                'destination_sku' => 'TEST-' . $catalogItem['asin']
                            ];

                            $this->info("\nAdding product to seller catalog...");
                            try {
                                $result = $client->addToSellerCatalog($testProduct);
                                if ($result) {
                                    $this->info("✅ Product added successfully to seller catalog");
                                } else {
                                    $this->error("❌ Failed to add product to seller catalog");
                                }
                            } catch (\Exception $e) {
                                $this->error("❌ Failed to add to catalog: " . $e->getMessage());
                            }
                        }
                    } else {
                        $this->warn("\n⚠️ Product not found in Amazon catalog. Cannot add to seller catalog.");
                    }
                } else {
                    $this->error("❌ Authentication failed - No response received");
                }
            } catch (\Exception $e) {
                $this->error("❌ Authentication test failed: " . $e->getMessage());
                Log::error('Amazon SP-API Authentication Test Failed', [
                    'connection_pair_id' => $connectionPair->id,
                    'destination_id' => $connectionPair->destination->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return 1;
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Test failed: " . $e->getMessage());
            Log::error('Amazon SP-API Test Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
} 