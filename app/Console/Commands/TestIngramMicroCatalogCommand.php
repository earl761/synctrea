<?php

namespace App\Console\Commands;

use App\Models\Supplier;
use App\Services\Api\IngramMicroApiClient;
use Illuminate\Console\Command;

class TestIngramMicroCatalogCommand extends Command
{
    protected $signature = 'ingram:test-catalog
        {--page=1 : The page number to retrieve}
        {--limit=25 : Number of items per page (max 100)}
        {--vendor= : Filter by vendor name}
        {--keyword= : Search by keyword}
        {--sku= : Filter by vendor part number}'
    ;

    protected $description = 'Test the Ingram Micro catalog API integration';

    public function handle(): int
    {
        try {
            $supplier = Supplier::where('type', 'ingram_micro')
                ->where('is_active', true)
                ->firstOrFail();

            $client = new IngramMicroApiClient($supplier);
            $client->initialize();

            $params = [
                'pageNumber' => $this->option('page'),
                'pageSize' => $this->option('limit'),
            ];

            if ($vendor = $this->option('vendor')) {
                $params['vendor'] = $vendor;
            }

            if ($keyword = $this->option('keyword')) {
                $params['keyword'] = $keyword;
            }

            if ($sku = $this->option('sku')) {
                $params['vendorPartNumber'] = $sku;
            }

            $this->info('Fetching catalog data...');
            $result = $client->getCatalog($params);

            $this->table(
                ['SKU', 'Vendor', 'Description', 'Price'],
                collect($result['products'] ?? [])->map(function ($product) {
                    return [
                        $product['vendorPartNumber'] ?? 'N/A',
                        $product['vendor'] ?? 'N/A',
                        $product['description'] ?? 'N/A',
                        $product['price']['customerPrice'] ?? 'N/A',
                    ];
                })
            );

            $this->info(sprintf(
                'Page %d of %d (Total records: %d)',
                $result['currentPage'] ?? 1,
                $result['totalPages'] ?? 1,
                $result['totalResults'] ?? 0
            ));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}