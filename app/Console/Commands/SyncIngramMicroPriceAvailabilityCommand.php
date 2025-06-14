<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\SyncLog;
use App\Services\Api\IngramMicroApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncIngramMicroPriceAvailabilityCommand extends Command
{
    protected $signature = 'ingram:sync-price-availability
        {--chunk=25 : Number of products to process per API call (reduced for rate limiting)}
        {--force : Force sync even if there was a recent successful sync}'
    ;

    protected $description = 'Sync product prices and availability from Ingram Micro';

    public function handle(): int
    {
        // Get the supplier first to ensure it exists before creating the sync log
        $supplier = Supplier::where('type', 'ingram_micro')
            ->where('is_active', true)
            ->firstOrFail();

        Log::info('Supplier: '. $supplier->id);
        // Create a new sync log for this sync

        $syncLog = new SyncLog([
            'supplier_id' => $supplier->id,
            'type' => 'ingram_micro_price_availability',
            'status' => 'running',
            'started_at' => now(),
        ]);
        $syncLog->save();

        try {
            // Check for recent successful sync unless forced
            if (!$this->option('force')) {
                $recentSync = SyncLog::where('type', 'ingram_micro_price_availability')
                    ->where('status', 'completed')
                    ->where('created_at', '>=', now()->subHours(1))
                    ->exists();

                if ($recentSync) {
                    $this->warn('A successful sync was performed in the last hour. Use --force to override.');
                    return Command::FAILURE;
                }
            }

            $client = new IngramMicroApiClient($supplier);

            $client->initialize();

            $chunkSize = min((int) $this->option('chunk') ?: 25, 50);
            $totalProcessed = 0;
            $totalUpdated = 0;


            $this->info('Starting Ingram Micro price and availability sync...');

            // Process products in chunks
            Product::where('supplier_id', $supplier->id)
                ->whereNotNull('sku')
                ->chunkById($chunkSize, function ($products) use ($client, &$totalProcessed, &$totalUpdated) {
                    $productData = [];
                    
                    foreach ($products as $product) {
                        // Priority: ingramPartNumber (SKU) > vendorPartNumber > UPC
                        $identifier = [];
                        if (!empty($product->sku)) {
                            $identifier['ingramPartNumber'] = $product->sku;
                        } elseif (!empty($product->part_number)) {
                            $identifier['vendorPartNumber'] = $product->part_number;
                        } elseif (!empty($product->upc)) {
                            $identifier['upc'] = $product->upc;
                        }
                        
                        // Only add products that have a valid identifier
                        if (!empty($identifier)) {
                            $productData[] = $identifier;
                        }
                    }

                    Log::info('Processing products with identifiers: ' . json_encode($productData));

                    try {
                        $result = $client->getPriceAndAvailability([
                            'products' => $productData,
                            'includeAvailability' => 'true',
                            'includePricing' => 'true',
                            'showAvailableDiscounts' => 'true',
                        ]);

                        DB::beginTransaction();
                        try {
                            foreach ($result as $item) {

                                //::info('Item: '. json_encode($item));


                                // {
                                //     "ingramPartNumber": "5348387",
                                //     "vendorPartNumber": "20VE0117MH",
                                //     "productAuthorized": "True",
                                //     "description": "TB 15 G2 CI5-1135G7 8/256GB 15.6 W11P",
                                //     "upc": "196119831243",
                                //     "productCategory": "Computers Test Duplicate",
                                //     "productSubcategory": "Notebooks & Tablets",
                                //     "vendorName": "Lenovo",
                                //     "vendorNumber": "0000500082",
                                //     "productStatusCode": null,
                                //     "productClass": null,
                                //     "indicators": {
                                //       "hasWarranty": true,
                                //       "isNewProduct": false,
                                //       "hasReturnLimits": false,
                                //       "isBackOrderAllowed": false,
                                //       "isShippedFromPartner": false,
                                //       "isReplacementProduct": false,
                                //       "replacementType": null,
                                //       "isDirectship": false,
                                //       "isDownloadable": false,
                                //       "isDigitalType": false,
                                //       "skuType": "M",
                                //       "hasStdSpecialPrice": false,
                                //       "hasAcopSpecialPrice": false,
                                //       "hasAcopQuantityBreak": false,
                                //       "hasStdWebDiscount": false,
                                //       "hasAcopWebDiscount": false,
                                //       "hasSpecialBid": false,
                                //       "isExportableToCountry": false,
                                //       "isDiscontinuedProduct": true,
                                //       "isRefurbished": false,
                                //       "isReturnableProduct": false,
                                //       "isIngramShip": true,
                                //       "isEnduserRequired": false,
                                //       "isHeavyWeight": false,
                                //       "hasLtl": false,
                                //       "isClearanceProduct": false,
                                //       "hasBundle": false,
                                //       "isOversizeProduct": false,
                                //       "isPreorderProduct": false,
                                //       "isLicenseProduct": false,
                                //       "isDirectshipOrderable": true,
                                //       "isServiceSku": false,
                                //       "isConfigurable": false
                                //     },
                                //     "ciscoFields": {
                                //       "productSubGroup": null,
                                //       "serviceProgramName": null,
                                //       "itemCatalogCategory": null,
                                //       "configurationIndicator": null,
                                //       "internalBusinessEntity": null,
                                //       "itemType": null,
                                //       "globalListPrice": null
                                //     },
                                //     "warrantyInformation": [],
                                //     "additionalInformation": {
                                //       "productWeight": [
                                //         {
                                //           "plantId": "NL01",
                                //           "weight": 2.6,
                                //           "weightUnit": "KG"
                                //         }
                                //       ],
                                //       "isBulkFreight": false,
                                //       "height": "8",
                                //       "width": "31",
                                //       "length": "50",
                                //       "netWeight": null,
                                //       "dimensionUnit": "CM"
                                //     }
                                //   }

                                $sku = $item['ingramPartNumber'] ?? null;
                                if (!$sku) continue;

                               

                                $sku = $item['ingramPartNumber'] ?? null;
                                if (!$sku) continue;
                                
                                $product = $products->firstWhere('sku', $sku);
                                if (!$product) continue;

                                $pricing = $item['pricing'] ?? [];
                                //Log::info('Pricing: '. json_encode($pricing));
                                $availability = $item['availability'] ?? [];
                               // Log::info('Availability: '. json_encode($availability));

                                $product->update([
                                    'cost_price' => $pricing['customerPrice'] ?? 0,
                                    'currency_code' => $pricing['currencyCode'] ?? 'USD',
                                    'retail_price' => $pricing['retailPrice'] ?? 0,
                                    'map_price' => $pricing['mapPrice'] ?? 0,
                                    'stock_quantity' => $availability['totalAvailability'] ?? 0,
                                    'metadata' => array_merge(
                                        $product->metadata ?? [],
                                        [
                                            'pricing' => $pricing,
                                            'availability' => $availability,
                                        ]
                                    ),
                                    // 'weight' => $weight,
                                    // 'weight_unit' => $weightUnit,
                                    // 'height' => $height,
                                    // 'width' => $width,
                                    // 'length' => $length,
                                    // 'net_weight' => $netWeight,
                                    //'synced_at' => now(),
                                ]);

                                $totalUpdated++;
                            }
                            DB::commit();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            throw $e;
                        }

                        $totalProcessed += count($productData);
                        $this->info(sprintf(
                            'Processed %d products, updated %d',
                            $totalProcessed,
                            $totalUpdated
                        ));
                        $this->info('Processing product details...');
                        foreach ($productData as $productIdentifier) {
                            $sku = $productIdentifier['ingramPartNumber'] ?? null;
                            if (!$sku) continue;
                            
                            $product = $products->firstWhere('sku', $sku);
                            if (!$product) continue;

                            $productDetails = $client->getProductDetails($sku);

                            $weight = $productDetails['additionalInformation']['productWeight'][0]['weight'] ?? null;
                            $weightUnit = $productDetails['additionalInformation']['productWeight'][0]['weightUnit'] ?? null;
                            $height = $productDetails['additionalInformation']['height'] ?? null;
                            $width = $productDetails['additionalInformation']['width'] ?? null;
                            $length = $productDetails['additionalInformation']['length'] ?? null;
                            $netWeight = $productDetails['additionalInformation']['netWeight'] ?? null;
                            $dimensionUnit = $productDetails['additionalInformation']['dimensionUnit'] ?? null;
                            $isBulkFreight = $productDetails['additionalInformation']['isBulkFreight'] ?? null;

                            $product->update([
                                'weight' => $weight,
                                'weight_unit' => $weightUnit,
                                'height' => $height,
                                'width' => $width,
                                'length' => $length,
                                'net_weight' => $netWeight,
                                'dimension_unit' => $dimensionUnit,
                                //'is_bulk_freight' => $isBulkFreight,
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('Failed to process chunk: ' . $e->getMessage(), [
                            'productData' => $productData,
                            'exception' => $e,
                        ]);
                        $this->error('Failed to process chunk: ' . $e->getMessage());
                    }
                });

                

                
            

            $syncLog->update([
                'status' => 'completed',
                'completed_at' => now(),
                'metadata' => [
                    'total_processed' => $totalProcessed,
                    'total_updated' => $totalUpdated,
                ],
            ]);



            $this->info(sprintf(
                'Sync completed. Processed %d products, updated %d',
                $totalProcessed,
                $totalUpdated
            ));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Ingram Micro price and availability sync failed: ' . $e->getMessage(), [
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