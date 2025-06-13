<?php

namespace App\Console\Commands;

use App\Models\Supplier;
use App\Models\ConnectionPair;
use App\Models\ConnectionPairProduct;
use App\Models\Product;
use App\Models\SyncLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;

class IngramMicroFeedUpdateCommand extends Command
{
    protected $signature = 'ingram:feed-update {connectionPairId?}';
    protected $description = 'Download and process Ingram Micro price/inventory feed files';

    protected Filesystem $sftp;
    protected string $localPath;
    protected ?ConnectionPair $connectionPair = null;

    public function handle(): int
    {
        try {
            if ($connectionPairId = $this->argument('connectionPairId')) {
                $this->connectionPair = ConnectionPair::findOrFail($connectionPairId);
                $supplier = $this->connectionPair->supplier;
            } else {
                $supplier = Supplier::where('type', Supplier::TYPE_INGRAM_MICRO)
                    ->where('is_active', true)
                    ->firstOrFail();

                
            }
            Log::info('Syncing Ingram Micro catalog', [
                'supplier' => $supplier->name,
                'supplier_id' => $supplier->id,
                'credentials' => $supplier->credentials,
                'credentials_sftp_host' => $supplier->credentials['sftp_host']?? '',
                'credentials_sftp_username' => $supplier->credentials['sftp_username']?? '',
                'credentials_sftp_password' => $supplier->credentials['sftp_password']?? '',
            ]);

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
        
        // Strip 'sftp://' protocol from host if present
        $host = str_replace('sftp://', '', $credentials['sftp_host']);
        
        $this->sftp = new Filesystem(new SftpAdapter(
            new SftpConnectionProvider(
                $host,
                $credentials['sftp_username'],
                $credentials['sftp_password'] 
               // C,00FM05      ,352U,JUNIPER H/E SW SRX BRANCH SRX LIC  ,1YR SIGNATURE SUB              ,,0000000000027570.00,SRX5K-IDP           ,000000.00,             ,0000.00,0000.00,0000.00,Y,0000000000019299.00,O,N, ,SM-SW ,LICS,1569, , ,            
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
        $contents = $this->sftp->read($remoteFile);
        file_put_contents($localFile, $contents);

        // Extract ZIP file
        $zip = new \ZipArchive;
        if ($zip->open($localFile) === true) {
            $zip->extractTo($this->localPath);
            $zip->close();
            unlink($localFile); // Remove ZIP after extraction
        }
    }

    protected function processFeedFile(Supplier $supplier): void
    {
        $priceFile = $this->localPath . '/PRICE.TXT';
        if (!file_exists($priceFile)) {
            throw new \Exception("Price file not found after extraction");
        }

        $handle = fopen($priceFile, 'r');
        $lineCount = 0;
        $processedSkus = [];

        $stats = [
            'created' => 0,
            'updated' => 0,
            'deleted' => 0
        ];

        while (($line = fgetcsv($handle)) !== false) {
            $lineCount++;
            if ($lineCount === 1) continue; // Skip header row

            // Map CSV fields to product data
            $productData = [
                'ingram_sku' => $line[0] ?? null,
                'name' => trim($line[1] ?? ''),
                'part_number' => trim($line[3] ?? ''),
                'upc' => trim($line[4] ?? ''),
                'weight' => is_numeric($line[9]) ? min((float)$line[9], 999999.99) : 0,
                'price' => is_numeric($line[13]) ? (float)$line[13] : 0,
                'stock' => (int)($line[16] ?? 0),
            ];

            if ($productData['ingram_sku']) {
                $processedSkus[] = $productData['ingram_sku'];
                
                // First update or create the main Product
                $product = Product::updateOrCreate(
                    [
                        'supplier_id' => $this->connectionPair->supplier_id,
                        'sku' => $productData['ingram_sku']
                    ],
                    [
                        'name' => $productData['name'],
                        'part_number' => $productData['part_number'],
                        'upc' => $productData['upc'],
                        'cost_price' => $productData['price'],
                        'stock_quantity' => $productData['stock'],
                    ]
                );

                // Then process connection pair product
                if ($this->connectionPair) {
                    $result = $this->processConnectionPairProduct($productData);
                } else {
                    $result = $this->processAllConnectionPairProducts($supplier, $productData);
                }
                
                $stats['created'] += $result['created'];
                $stats['updated'] += $result['updated'];
            }
        }

        fclose($handle);

        // Handle deletions - products in DB but not in feed
        if ($this->connectionPair) {
            $stats['deleted'] = $this->handleDeletions($this->connectionPair, $processedSkus);
        } else {
            foreach ($supplier->connectionPairs()->where('is_active', true)->get() as $connectionPair) {
                $stats['deleted'] += $this->handleDeletions($connectionPair, $processedSkus);
            }
        }

        $this->info("Feed processing completed:");
        $this->info(" - Created: {$stats['created']}");
        $this->info(" - Updated: {$stats['updated']}");
        $this->info(" - Deleted: {$stats['deleted']}");
        
        // Cleanup
        unlink($priceFile);
    }

    protected function processConnectionPairProduct(array $productData): array
    {
        $stats = ['created' => 0, 'updated' => 0];
        
        $product = Product::where('supplier_id', $this->connectionPair->supplier_id)
            ->where('sku', $productData['ingram_sku'])
            ->first();

        if ($product) {
            $product->update([
                'name' => $productData['name'],
                'part_number' => $productData['part_number'],
                'upc' => $productData['upc'],
                // 'weight' => $productData['weight'],
                'cost_price' => $productData['price'],
                'stock_quantity' => $productData['stock'],
            ]);
            $stats['updated']++;
        } else {
            Product::create([
                'supplier_id' => $this->connectionPair->supplier_id,
                'sku' => $productData['ingram_sku'],
                'name' => $productData['name'],
                'part_number' => $productData['part_number'],
                'upc' => $productData['upc'],
                //'weight' => $productData['weight'],
                'cost_price' => $productData['price'],
                'stock_quantity' => $productData['stock'],
       
            ]);
            $stats['created']++;
        }

        return $stats;
    }

    protected function processAllConnectionPairProducts(Supplier $supplier, array $productData): array
    {
        $stats = ['created' => 0, 'updated' => 0];
        $connectionPairs = $supplier->connectionPairs()->where('is_active', true)->get();

        foreach ($connectionPairs as $connectionPair) {
            $product = ConnectionPairProduct::where('connection_pair_id', $connectionPair->id)
                ->where('sku', $productData['ingram_sku'])
                ->first();

            if ($product) {
                $product->update([
                    'name' => $productData['name'],
                    'part_number' => $productData['part_number'],
                    'upc' => $productData['upc'],
                    // 'weight' => $productData['weight'],
                    'cost_price' => $productData['price'],
                    'stock_quantity' => $productData['stock'],
                    // 'last_synced_at' => now()
                ]);
                $stats['updated']++;
            } else {
               

            }
        }

        return $stats;
    }

    protected function handleDeletions(ConnectionPair $connectionPair, array $processedSkus): int
    {
        $deletedCount = 0;
        
        // Find and zero stock for products that exist in DB but not in the feed
        $productsToDelete = Product::where('supplier_id', $connectionPair->supplier_id)
            ->whereNotIn('sku', $processedSkus)
            ->get();

        foreach ($productsToDelete as $product) {
            SyncLog::create([
                'supplier_id' => $connectionPair->supplier_id,
                'product_id' => $product->id,
                'type' => 'zero_stock',
                'status' => 'success',
                'message' => 'Product stock set to 0 - not found in Ingram Micro feed',
                'details' => json_encode([
                    'old_data' => $product->toArray(),
                    'new_data' => array_merge($product->toArray(), ['stock_quantity' => 0])
                ]),
            ]);

            $product->update(['stock_quantity' => 0]);
            $deletedCount++;
        }

        // Also zero stock for connection pair products
        $connectionPairProductsToDelete = ConnectionPairProduct::where('connection_pair_id', $connectionPair->id)
            ->whereNotIn('sku', $processedSkus)
            ->get();

        foreach ($connectionPairProductsToDelete as $product) {
            SyncLog::create([
                'supplier_id' => $connectionPair->supplier_id,
                'product_id' => $product->id,
                'type' => 'zero_stock',
                'status' => 'success',
                'message' => 'Connection pair product stock set to 0 - not found in Ingram Micro feed',
                'details' => json_encode([
                    'old_data' => $product->toArray(),
                    'new_data' => array_merge($product->toArray(), ['stock_quantity' => 0])
                ]),
            ]);

            $product->update(['stock_quantity' => 0]);
            $deletedCount++;
        }

        return $deletedCount;
    }
}