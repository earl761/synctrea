<?php

namespace App\Console\Commands;

use App\Models\Destination;
use App\Services\Api\NeweggApiClient;
use Illuminate\Console\Command;
use Exception;

class NeweggSubmitExistingItemFeedCommand extends Command
{
    protected $signature = 'newegg:submit-existing-item-feed 
                            {--destination-id= : The Newegg destination ID}
                            {--file= : JSON file containing items to submit}
                            {--test-item : Submit a single test item}';

    protected $description = 'Submit existing item creation feed to Newegg Marketplace';

    public function handle(): int
    {
        try {
            $destinationId = $this->option('destination-id');
            $file = $this->option('file');
            $testItem = $this->option('test-item');

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

            $this->info('Connecting to Newegg API for destination: ' . $destination->name);

            // Initialize API client
            $client = new NeweggApiClient($destination);
            $client->initialize();

            $items = [];

            if ($testItem) {
                // Create a test item
                $items = [[
                    'sku' => 'TEST-SKU-' . time(),
                    'manufacturer' => 'Test Manufacturer',
                    'manufacturer_part_number' => 'TEST-MPN-123',
                    'upc' => '123456789012',
                    'price' => 99.99,
                    'quantity' => 10,
                    'shipping' => 'Default',
                    'condition' => 'New',
                    'activation_mark' => 'True',
                    'msrp' => 129.99,
                    'warranty' => 'Manufacturer Warranty'
                ]];
                
                $this->info('Submitting test item with SKU: ' . $items[0]['sku']);
            } elseif ($file) {
                if (!file_exists($file)) {
                    $this->error('File not found: ' . $file);
                    return 1;
                }

                $content = file_get_contents($file);
                $items = json_decode($content, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->error('Invalid JSON file: ' . json_last_error_msg());
                    return 1;
                }

                $this->info('Submitting ' . count($items) . ' items from file: ' . $file);
            } else {
                $this->error('Please provide either --file option with JSON file or --test-item flag');
                return 1;
            }

            // Validate items
            foreach ($items as $index => $item) {
                if (empty($item['sku']) || empty($item['manufacturer'])) {
                    $this->error("Item at index {$index} is missing required fields (sku, manufacturer)");
                    return 1;
                }

                // Check if at least one identifier is provided
                if (empty($item['manufacturer_part_number']) && 
                    empty($item['upc']) && 
                    empty($item['newegg_item_number'])) {
                    $this->error("Item at index {$index} must have at least one identifier (manufacturer_part_number, upc, or newegg_item_number)");
                    return 1;
                }
            }

            // Submit feed
            $result = $client->submitExistingItemCreationFeed($items);

            $this->info('Feed submitted successfully!');
            $this->line('Response:');
            $this->line(json_encode($result, JSON_PRETTY_PRINT));

            if (isset($result['RequestId'])) {
                $this->info('Request ID: ' . $result['RequestId']);
                $this->info('You can check the feed status using the Request ID.');
            }

            return 0;

        } catch (Exception $e) {
            $this->error('Failed to submit feed: ' . $e->getMessage());
            return 1;
        }
    }
}