<?php

namespace App\Console\Commands;

use App\Models\Supplier;
use App\Models\Product;
use App\Models\SyncLog;
use App\Services\Api\IngramMicroApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use Illuminate\Support\Collection;

class IngramMicroFeedUpdateCommand extends Command
{
    protected $signature = 'ingram:feed-update';
    protected $description = 'Download and process Ingram Micro price/inventory feed files';

    protected Filesystem $sftp;
    protected string $localPath;
    protected IngramMicroApiClient $ingramMicroApiClient;

    public function handle(): int
    {
        try {
            // Get Ingram Micro supplier directly - we work with Products, not ConnectionPairs
            $supplier = Supplier::where('type', Supplier::TYPE_INGRAM_MICRO)
                ->where('is_active', true)
                ->firstOrFail();
            
            // Initialize Ingram Micro API client
            $this->ingramMicroApiClient = new IngramMicroApiClient($supplier);
           

            if (!$supplier->credentials || empty($supplier->credentials['sftp_host'])) {
                $this->error('SFTP credentials not configured for supplier');
                return 1;
            }

            $this->setupSftpConnection($supplier);
            $this->localPath = storage_path('app/ingram-feeds/' . $supplier->id);
            
            if (!file_exists($this->localPath)) {
                mkdir($this->localPath, 0755, true);
            }

            // Download and process price file
            $this->downloadFeedFile($supplier);
            
            // Verify the file was downloaded and extracted
            $priceFile = $this->localPath . '/PRICE.TXT';
            if (!file_exists($priceFile)) {
                throw new \Exception("PRICE.TXT file not found after download and extraction");
            }
            
            $this->processFeedFile($supplier);

            $this->info('Feed update completed successfully');
            return 0;

        } catch (\Exception $e) {
            Log::error('Ingram Micro feed update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->error($e->getMessage());
            return 1;
        }
    }

    protected function setupSftpConnection(Supplier $supplier): void
    {
        $credentials = $supplier->credentials;
        
        // Strip 'sftp://' protocol from host if present and trim whitespace
        $host = trim(str_replace('sftp://', '', $credentials['sftp_host']));
        $username = trim($credentials['sftp_username']);
        $password = trim($credentials['sftp_password']);
        
        $this->info("Connecting to SFTP: {$host} with username: {$username}");
        
        $this->sftp = new Filesystem(new SftpAdapter(
            new SftpConnectionProvider(
                $host,
                $username,
                $password
            ),
            '/', // Root path
            PortableVisibilityConverter::fromArray([
                'file' => [
                    'public' => 0644,
                    'private' => 0600,
                ],
                'dir' => [
                    'public' => 0755,
                    'private' => 0700,
                ],
            ])
        ));
    }

    protected function downloadFeedFile(Supplier $supplier): void
    {
        $remoteFile = $supplier->credentials['sftp_path'] ?? '/PRICE.ZIP';
        $localFile = $this->localPath . '/' . basename($remoteFile);

        $this->info("Downloading feed file {$remoteFile}");
        
        try {
            // Check if remote file exists
            if (!$this->sftp->fileExists($remoteFile)) {
                throw new \Exception("Remote file {$remoteFile} does not exist");
            }
            
            $contents = $this->sftp->read($remoteFile);
            $this->info("Downloaded " . strlen($contents) . " bytes");
            
            file_put_contents($localFile, $contents);
            $this->info("Saved to {$localFile}");

            // Extract ZIP file
            $zip = new \ZipArchive;
            $result = $zip->open($localFile);
            if ($result === true) {
                $this->info("Extracting ZIP file to {$this->localPath}");
                $zip->extractTo($this->localPath);
                $zip->close();
                // Keep ZIP file for future runs - don't delete it
                $this->info("ZIP file extracted successfully");
            } else {
                throw new \Exception("Failed to open ZIP file. Error code: {$result}");
            }
        } catch (\Exception $e) {
            throw new \Exception("Failed to download or extract feed file: " . $e->getMessage());
        }
    }

    protected function processFeedFile(Supplier $supplier): void
    {
        $priceFile = $this->localPath . '/PRICE.TXT';
        $this->info("Looking for price file at: {$priceFile}");
        
        if (!file_exists($priceFile)) {
            throw new \Exception("Price file not found after extraction");
        }
        
        $fileSize = filesize($priceFile);
        $this->info("Processing price file: {$priceFile} (Size: {$fileSize} bytes)");
        
        // Count total lines for progress tracking
        $totalLines = $this->countFileLines($priceFile);
        $this->info("Total lines to process: {$totalLines}");
        
        // Initialize progress bar
        $progressBar = $this->output->createProgressBar($totalLines);
        $progressBar->setFormat('Processing: %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% Memory: %memory:6s%');
        $progressBar->start();

        $handle = fopen($priceFile, 'r');
        $lineCount = 0;
        $processedSkus = [];
        $batchData = [];
        $batchSize = 100; // Process in batches of 100

        $stats = [
            'created' => 0,
            'updated' => 0,
            'deleted' => 0
        ];

        while (($line = fgets($handle)) !== false) {
            $lineCount++;
            $progressBar->advance();
            
            // Memory monitoring - log every 1000 lines
            if ($lineCount % 1000 === 0) {
                $memoryUsage = memory_get_usage(true) / 1024 / 1024; // MB
                $peakMemory = memory_get_peak_usage(true) / 1024 / 1024; // MB
                $this->newLine();
                //$this->info("Memory usage: {$memoryUsage:.2f}MB (Peak: {$peakMemory:.2f}MB)");
                $progressBar->display();
            }
            
            // Skip empty lines
            $line = trim($line);
            if (empty($line)) continue;
            
            // Parse comma-delimited line
            $fields = explode("\t", $line);
            
            // Add debugging for first few lines
            if ($lineCount <= 5) {
                $this->newLine();
                $this->info("Line {$lineCount}: " . count($fields) . " fields");
                $this->info("Sample fields: " . implode(' | ', array_slice($fields, 0, 5)));
                $progressBar->display();
            }
            
            // Skip if not enough fields or invalid format
            if (count($fields) < 10) {
                if ($lineCount <= 5) {
                    $this->newLine();
                    $this->warn("Skipping line {$lineCount}: insufficient fields (" . count($fields) . " fields)");
                    $progressBar->display();
                }
                continue;
            }

            // Map pipe-delimited fields to product data based on PRICE.TXT format
            // Field positions: 0=Type, 1=IngramSKU, 2=VendorNumber, 3=VendorName, 4=Description1, 5=Description2, 6=UnitPrice, 7=VendorPartNumber, 8=Weight, 9=UPC, etc.
            $productData = [
                'ingram_sku' => trim($fields[1] ?? ''),
                'name' => trim(($fields[4] ?? '') . ' ' . ($fields[5] ?? '')), // Combine description fields
                'part_number' => trim($fields[7] ?? ''),
                'upc' => trim($fields[9] ?? ''),
                'weight' => $this->parseNumericField($fields[8] ?? '0'),
                'price' => $this->parseNumericField($fields[6] ?? '0'),
                'cost' => $this->parseNumericField($fields[14] ?? '0'),
                'vendor' => trim($fields[3] ?? ''),
                'description' => trim(($fields[4] ?? '') . ' ' . ($fields[5] ?? '')),
                'category' =>trim($fields[19] ?? ''),
                'type' => trim($fields[20] ?? ''),
               // 'authorizedToPurchase' => '',
                'height' => $this->parseNumericField($fields[10] ?? '0'),
                'width' =>$this->parseNumericField($fields[11] ?? '0'),
                'length' => $this->parseNumericField($fields[12] ?? '0'),
                'stock' => $this->parseNumericField($fields[21] ?? '0' ), // Stock info not available in this feed format
            ];

            // Add debugging for product data
            if ($lineCount <= 5) {
                $this->info("Product data: " . json_encode($productData));
            }
            
            if (!empty($productData['ingram_sku'])) {
                $processedSkus[] = $productData['ingram_sku'];
                
                // Add debugging for successful processing
                if ($lineCount <= 5) {
                    $this->newLine();
                    $this->info("Processing product with SKU: " . $productData['ingram_sku']);
                    $progressBar->display();
                }
                
                // Prepare update data for Product table
                $updateData = [
                    'name' => $productData['name'],
                    'part_number' => $productData['part_number'],
                    'upc' => $productData['upc'],
                    'cost_price' => $productData['cost'],
                    'retail_price' => $productData['price'],
                    'description' => $productData['description'],
                    'stock_quantity' => $productData['stock'],
                    'category' => $productData['category'],
                    'type' => $productData['type'],
                   // 'authorizedToPurchase' => $productData['authorizedToPurchase'],
                    'height' => $productData['height'],
                    'width' => $productData['width'],
                    'length' => $productData['length'],
                    'map_price' => $productData['price'],
                    'brand' => $productData['vendor'],
                    'condition' => 'new'
                ];
                
                // Add weight if it's a reasonable value
                if ($productData['weight'] > 0 && $productData['weight'] < 999999) {
                    $updateData['weight'] = $productData['weight'];
                }
                
                // Add to batch for processing
                $batchData[] = [
                    'search_criteria' => [
                        'supplier_id' => $supplier->id,
                        'sku' => $productData['ingram_sku']
                    ],
                    'update_data' => $updateData
                ];
                
                // Process batch when it reaches the batch size
                if (count($batchData) >= $batchSize) {
                    $batchStats = $this->processBatch($batchData, $supplier);
                    $stats['created'] += $batchStats['created'];
                    $stats['updated'] += $batchStats['updated'];
                    $batchData = []; // Reset batch
                }
                
                // Add debugging for processing results
                if ($lineCount <= 5) {
                    $this->newLine();
                    $this->info("Product added to batch for processing");
                    $progressBar->display();
                }
            } else {
                if ($lineCount <= 5) {
                    $this->newLine();
                    $this->info("Warning: Empty SKU found");
                    $progressBar->display();
                }
            }
        }

        // Process any remaining items in the final batch
        if (!empty($batchData)) {
            $batchStats = $this->processBatch($batchData, $supplier);
            $stats['created'] += $batchStats['created'];
            $stats['updated'] += $batchStats['updated'];
        }
        
        fclose($handle);
        $progressBar->finish();
        $this->newLine(2);
        
        // Final memory usage report
        $finalMemory = memory_get_usage(true) / 1024 / 1024;
        $peakMemory = memory_get_peak_usage(true) / 1024 / 1024;
        //$this->info("Final memory usage: {$finalMemory:.2f}MB (Peak: {$peakMemory:.2f}MB)");
        $this->info("Feed processing completed. Created: {$stats['created']}, Updated: {$stats['updated']}");

        // Clean up the extracted file
        unlink($priceFile);
    }
    
    /**
     * Count total lines in file for progress tracking
     */
    protected function countFileLines(string $filePath): int
    {
        $lineCount = 0;
        $handle = fopen($filePath, 'r');
        
        while (fgets($handle) !== false) {
            $lineCount++;
        }
        
        fclose($handle);
        return $lineCount;
    }
    
    /**
     * Process a batch of products for database operations
     */
    protected function processBatch(array $batchData, Supplier $supplier): array
    {
        $stats = ['created' => 0, 'updated' => 0];
        
        foreach ($batchData as $item) {
            try {
                $product = Product::updateOrCreate(
                    $item['search_criteria'],
                    $item['update_data']
                );
                
                if ($product->wasRecentlyCreated) {
                    $stats['created']++;
                } else {
                    $stats['updated']++;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to process product in batch', [
                    'sku' => $item['search_criteria']['sku'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $stats;
    }




    
    /**
     * Parse numeric field from PRICE.TXT format (removes leading zeros and converts to float)
     */
    protected function parseNumericField(string $value): float
    {
        // Remove leading zeros and convert to float
        $cleaned = ltrim($value, '0');
        if (empty($cleaned) || $cleaned === '.') {
            return 0.0;
        }
        return is_numeric($cleaned) ? (float)$cleaned : 0.0;
    }
    
    /**
     * Update product price and availability using Ingram Micro API
     */
    protected function updateProductPriceAndAvailability(string $partNumber): void
    {
        try {
            $this->info("Fetching price and availability for part number: {$partNumber}");
            
            $response = $this->ingramMicroApiClient->getPriceAndAvailability([
                'partNumber' => $partNumber
            ]);
            
            if (!empty($response['data'])) {
                foreach ($response['data'] as $productInfo) {
                    $ingramSku = $productInfo['ingramPartNumber'] ?? null;
                    
                    if ($ingramSku) {
                        // Update main Product table
                        $product = Product::where('supplier_id', $this->ingramMicroApiClient->getSupplier()->id)
                            ->where('sku', $ingramSku)
                            ->first();
                            
                        if ($product) {
                            $updateData = [];
                            
                            // Update price if available
                            if (isset($productInfo['pricing']['customerPrice'])) {
                                $updateData['cost_price'] = (float) $productInfo['pricing']['customerPrice'];
                            }
                            
                            // Update stock if available
                            if (isset($productInfo['availability']['availabilityByWarehouse'])) {
                                $totalStock = 0;
                                foreach ($productInfo['availability']['availabilityByWarehouse'] as $warehouse) {
                                    $totalStock += (int) ($warehouse['quantityAvailable'] ?? 0);
                                }
                                $updateData['stock_quantity'] = $totalStock;
                            }
                            
                            if (!empty($updateData)) {
                                $product->update($updateData);
                                $this->info("Updated product {$ingramSku} with latest price and availability");
                            }
                        }
                        

                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to update price and availability for part number: ' . $partNumber, [
                'error' => $e->getMessage(),
                'part_number' => $partNumber
            ]);
            $this->warn("Failed to update price and availability for {$partNumber}: {$e->getMessage()}");
        }
    }
}