<?php

namespace App\Console\Commands;

use App\Models\ConnectionPair;
use App\Models\Product;
use App\Services\Api\AmazonApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\ConnectionPairProduct;
use App\Models\AmazonFeed;
use Carbon\Carbon;


class AmazonBulkCatalogUpdateCommand extends Command
{
    protected $signature = 'amazon:bulk-catalog-update {connectionPairId}';
    protected $description = 'Update Amazon catalog in bulk using feeds API with dynamic product data';

    public function handle()
    {
        $connectionPairId = $this->argument('connectionPairId');

        try {
            $connectionPair = ConnectionPair::findOrFail($connectionPairId);
            $amazonApiClient = new AmazonApiClient($connectionPair);

            $items = $this->getItemsToUpdate($connectionPairId);
            if (empty($items)) {
                $this->info('No valid products found to update.');
                Log::info('No valid products found for bulk catalog update', [
                    'connection_pair_id' => $connectionPairId
                ]);
                return 0;
            }

            $this->info('Found ' . count($items) . ' products with required data');
            foreach ($items as $item) {
                $this->info(sprintf(
                    'Preparing update for SKU %s: Price $%s, Quantity %d',
                    $item['sku'],
                    number_format($item['sellerPrice'], 2),
                    $item['quantity']
                ));
            }

            $this->info('Proceeding with update for ' . count($items) . ' items...');
            $this->info('This may take a while as Amazon processes the feeds...');

            $result = $amazonApiClient->updateBulkListingPricesWithFeed($items);
            $feedId = $result['feed_id'];

            // Create feed record
        $amazonFeed = AmazonFeed::create([
            'connection_pair_id' => $connectionPairId,
            'feed_id' => $feedId,
            'feed_type' => 'POST_FLAT_FILE_PRICEANDQUANTITYONLY_UPDATE_DATA',
            'processing_status' => 'IN_PROGRESS',
            'feed_document_id' => $result['feed_document_id'] ?? null,
            'processing_start_time' => Carbon::now(),
        ]);


            $maxAttempts = 30;
            $attempt = 0;
            $delaySeconds = 10;

            while ($attempt < $maxAttempts) {
                $feedStatus = $amazonApiClient->getFeedStatus($feedId);
                $status = $feedStatus['processingStatus'] ?? 'UNKNOWN';

                 // Update feed status in database
            $amazonFeed->update([
                'processing_status' => $status
            ]);

                $this->info("Feed status: $status (Attempt " . ($attempt + 1) . "/$maxAttempts)");

                if ($status === 'DONE' && isset($feedStatus['resultFeedDocumentId'])) {
                    $feedResult = $amazonApiClient->getFeedResult($feedStatus['resultFeedDocumentId']);
                    
                    // Update feed record with results
                $amazonFeed->update([
                    'result_feed_document_id' => $feedStatus['resultFeedDocumentId'],
                    'processing_end_time' => Carbon::now(),
                    'result_summary' => $this->summarizeFeedResult($feedResult),
                ]);

                    $this->processFeedResult($feedResult, $items, $connectionPairId);

                    $this->info('Amazon bulk catalog update completed successfully');
                    return 0;
                } elseif (in_array($status, ['CANCELLED', 'FATAL'])) {
                    Log::error('Feed processing failed', [
                        'feed_id' => $feedId,
                        'status' => $status,
                        'connection_pair_id' => $connectionPairId
                    ]);

                    // Update feed record with error status
                $amazonFeed->update([
                    'processing_status' => $status,
                    'processing_end_time' => Carbon::now(),
                    'errors' => ['message' => "Feed processing failed with status: $status"]
                ]);

                    throw new \Exception("Feed processing failed with status: $status");
                }

                $attempt++;
                if ($attempt < $maxAttempts) {
                    sleep($delaySeconds);
                }
            }

            // Update feed record with timeout error
        $amazonFeed->update([
            'processing_status' => 'TIMEOUT',
            'processing_end_time' => Carbon::now(),
            'errors' => ['message' => "Feed processing timed out after {$maxAttempts} attempts"]
        ]);


            Log::error('Feed processing timed out', [
                'feed_id' => $feedId,
                'attempts' => $maxAttempts,
                'connection_pair_id' => $connectionPairId
            ]);
            throw new \Exception('Feed processing timed out after ' . $maxAttempts . ' attempts');

        } catch (\Exception $e) {
            // Update feed record with error if it exists
        if (isset($amazonFeed)) {
            $amazonFeed->update([
                'processing_status' => 'ERROR',
                'processing_end_time' => Carbon::now(),
                'errors' => [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            ]);
        }

            Log::error('Amazon bulk catalog update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'connection_pair_id' => $connectionPairId
            ]);
            $this->error('Amazon bulk catalog update failed: ' . $e->getMessage());
            return 1;
        }
    }

    // Add this new method to create result summary
private function summarizeFeedResult(array $feedResult): array
{
    $summary = [
        'messages_processed' => 0,
        'messages_successful' => 0,
        'messages_with_error' => 0,
        'messages_with_warning' => 0,
    ];

    $results = $feedResult['Message']['ProcessingReport']['Result'] ?? [];
    if (!isset($results[0])) {
        $results = [$results];
    }

    foreach ($results as $res) {
        $summary['messages_processed']++;
        $resultCode = $res['ResultCode'] ?? 'Unknown';

        switch ($resultCode) {
            case 'Success':
                $summary['messages_successful']++;
                break;
            case 'Error':
                $summary['messages_with_error']++;
                break;
            case 'Warning':
                $summary['messages_with_warning']++;
                break;
        }
    }

    return $summary;
}

     /**
     * Generate the full SKU using the connection pair's prefix and the product's SKU
     */
    public function getPrefixedSku(Product $product): string
    {
        $prefix = ConnectionPair::find($this->argument('connectionPairId'))->sku_prefix ?? '';
        $productSku = $product->sku ?? '';

        if (empty($prefix) || empty($productSku)) {
            Log::warning('Missing SKU prefix or product SKU', [
                'prefix' => $prefix,
                'product_sku' => $productSku,
                'product_id' => $product->id
            ]);
        }

        return trim($prefix . $productSku);
    }

    private function getItemsToUpdate(int $connectionPairId): array
    {
        try {
            // Fetch products associated with the connection pair
            $products = ConnectionPairProduct::where('connection_pair_id', $connectionPairId)
                ->inCatalog()
                ->with('product')
                ->get();

            // $skuPrefix = ConnectionPair::find($connectionPairId)->sku_prefix ?? '';
            // if ($skuPrefix) {
            //     $products = $products->filter(function ($product) use ($skuPrefix) {
            //         return str_starts_with($product->sku, $skuPrefix);
            //     });
            // }

        
            $items = [];
            foreach ($products as $product) {
                // Validate SKU
                $sku = trim($product->sku);
                if (empty($sku) || !mb_check_encoding($sku, 'UTF-8')) {
                    Log::warning('Skipping product with invalid SKU', [
                        'product_id' => $product->id,
                        'sku' => $sku,
                        'connection_pair_id' => $connectionPairId
                    ]);
                    continue;
                }

                // Validate price
                $price = (float) $product->final_price;
                if ($price <= 0) {
                    Log::warning('Skipping product with invalid price', [
                        'product_id' => $product->id,
                        'sku' => $sku,
                        'price' => $price,
                        'connection_pair_id' => $connectionPairId
                    ]);
                    continue;
                }

                // Validate quantity
                $quantity = (int) $product->stock;
                if ($quantity < 0) {
                    Log::warning('Skipping product with invalid quantity', [
                        'product_id' => $product->id,
                        'sku' => $sku,
                        'quantity' => $quantity,
                        'connection_pair_id' => $connectionPairId
                    ]);
                    continue;
                }

                $items[] = [
                    'sku' => $sku,
                    'sellerPrice' => $price,
                    'quantity' => $quantity
                ];
            }

            Log::info('Retrieved products for bulk update', [
                'connection_pair_id' => $connectionPairId,
                'total_products' => $products->count(),
                'valid_items' => count($items)
            ]);

            return $items;
        } catch (\Exception $e) {
            Log::error('Failed to retrieve products for bulk update', [
                'connection_pair_id' => $connectionPairId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Failed to retrieve products: ' . $e->getMessage());
        }
    }

    private function processFeedResult(array $feedResult, array $items, int $connectionPairId): void
    {
        $messagesProcessed = 0;
        $messagesSuccessful = 0;
        $messagesWithError = 0;
        $messagesWithWarning = 0;

        $results = $feedResult['Message']['ProcessingReport']['Result'] ?? [];
        if (!isset($results[0])) {
            $results = [$results];
        }

        foreach ($results as $res) {
            $messagesProcessed++;
            $resultCode = $res['ResultCode'] ?? 'Unknown';

            if ($resultCode === 'Success') {
                $messagesSuccessful++;
            } elseif ($resultCode === 'Error') {
                $messagesWithError++;
                Log::error('Feed processing error for SKU', [
                    'sku' => $res['AdditionalInfo']['SKU'] ?? 'Unknown',
                    'error' => $res['ResultMessageCode'] . ': ' . $res['ResultDescription'],
                    'connection_pair_id' => $connectionPairId
                ]);
            } elseif ($resultCode === 'Warning') {
                $messagesWithWarning++;
                Log::warning('Feed processing warning for SKU', [
                    'sku' => $res['AdditionalInfo']['SKU'] ?? 'Unknown',
                    'warning' => $res['ResultMessageCode'] . ': ' . $res['ResultDescription'],
                    'connection_pair_id' => $connectionPairId
                ]);
            }
        }

        Log::info('Feed processing summary', [
            'messages_processed' => $messagesProcessed,
            'messages_successful' => $messagesSuccessful,
            'messages_with_error' => $messagesWithError,
            'messages_with_warning' => $messagesWithWarning,
            'connection_pair_id' => $connectionPairId
        ]);

        if ($messagesWithError > 0) {
            throw new \Exception('Feed processing completed with errors: ' . $messagesWithError . ' items failed');
        }
    }
}