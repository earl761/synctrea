<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\ConnectionPair;
use App\Models\ConnectionPairProduct;
use App\Services\Api\AmazonApiClient;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProductObserver
{
    protected $syncFields = [
        'sku' => 'sku',
        'name' => 'name',
        'upc' => 'upc',
        'condition' => 'condition',
        'part_number' => 'part_number',
        'cost_price' => 'price',
        'retail_price' => 'fila_price',
        'stock_quantity' => 'stock'
       
    ];

    public function created(Product $product)
    {
        Log::info('New product created, syncing to connection pairs', [
            'product_id' => $product->id,
            'supplier_id' => $product->supplier_id
        ]);

        $this->syncToConnectionPairs($product);
    }

    public function updated(Product $product)
    {
        $changedFields = array_intersect(
            array_keys($this->syncFields),
            array_keys($product->getDirty())
        );
        
        if (empty($changedFields)) {
            return;
        }

        Log::info('Product updated, syncing changes to connection pairs', [
            'product_id' => $product->id,
            'changed_fields' => $changedFields
        ]);

        $this->syncToConnectionPairs($product, $changedFields);

        // Check if in_catalog status changed
        if ($product->wasChanged('in_catalog')) {
            $oldStatus = $product->getOriginal('in_catalog');
            $newStatus = $product->in_catalog;

            Log::info('Product catalog status changed', [
                'product_id' => $product->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);

            // Handle deletion from catalog
            if ($newStatus === 'pending_deletion' || $newStatus === 'deleted') {
                $this->handleCatalogDeletion($product);
            }
        }
    }

    public function deleted(Product $product)
    {
        DB::beginTransaction();
        try {
            ConnectionPairProduct::where('product_id', $product->id)->delete();
            DB::commit();
            
            Log::info('Successfully deleted connection pair products', [
                'product_id' => $product->id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete connection pair products', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
        }

        // If product is deleted from our system, ensure it's removed from Amazon catalog
        if ($product->in_catalog !== 'deleted') {
            $this->handleCatalogDeletion($product);
        }
    }

    protected function syncToConnectionPairs(Product $product, array $specificFields = null)
    {
        $connectionPairs = ConnectionPair::where('supplier_id', $product->supplier_id)
            ->where('is_active', 1)
            ->get();

        if ($connectionPairs->isEmpty()) {
            Log::info('No active connection pairs found for supplier', [
                'supplier_id' => $product->supplier_id
            ]);
            return;
        }

        DB::beginTransaction();
        try {
            foreach ($connectionPairs as $connectionPair) {
                $data = [
                    'connection_pair_id' => $connectionPair->id,
                    'product_id' => $product->id,
                ];

                $attributes = [
                    'catalog_status' => ConnectionPairProduct::STATUS_DEFAULT,
                    'price_override_type' => 'none',
                ];

                if ($specificFields) {
                    // Update specific fields only
                    $updateData = $this->prepareUpdateData($product, $specificFields);
                    $attributes = array_merge($attributes, $updateData);
                } else {
                    // Update or create with all fields
                    foreach ($this->syncFields as $productField => $connectionField) {
                        $attributes[$connectionField] = $product->$productField;
                    }
                }

                ConnectionPairProduct::updateOrCreate($data, $attributes);
            }

            DB::commit();
            
            Log::info('Successfully synced connection pair products', [
                'product_id' => $product->id,
                'operation' => $specificFields ? 'update' : 'create/update',
                'affected_fields' => $specificFields ?? 'all'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to sync connection pair products', [
                'product_id' => $product->id,
                'operation' => $specificFields ? 'update' : 'create/update',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    protected function prepareUpdateData(Product $product, array $changedFields): array
    {
        $updateData = [];
        foreach ($changedFields as $field) {
            if (isset($this->syncFields[$field])) {
                $updateData[$this->syncFields[$field]] = $product->$field;
            }
        }
        return $updateData;
    }

    protected function prepareBulkData(Product $product, $connectionPairs): array
    {
        return $connectionPairs->map(function ($connectionPair) use ($product) {
            $data = [
                'connection_pair_id' => $connectionPair->id,
                'product_id' => $product->id,
                'catalog_status' => ConnectionPairProduct::STATUS_DEFAULT,
                'price_override_type' => 'none',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            foreach ($this->syncFields as $productField => $connectionField) {
                $data[$connectionField] = $product->$productField;
            }

            return $data;
        })->toArray();
    }

    public function saved(Product $product): void
    {
        if ($product->wasChanged(['cost_price', 'retail_price', 'supplier_id'])) {
            Event::dispatch(new \stdClass(['model' => $product]));
        }
    }

    /**
     * Handle deletion from Amazon catalog
     */
    private function handleCatalogDeletion(Product $product): void
    {
        try {
            // Get the connection pair associated with the product
            $connectionPair = $product->connectionPair;
            if (!$connectionPair) {
                Log::error('No connection pair found for product', [
                    'product_id' => $product->id
                ]);
                return;
            }

            // Initialize Amazon API client
            $amazonClient = new AmazonApiClient($connectionPair);

            // Attempt to delete from Amazon catalog
            $deleted = $amazonClient->deleteFromSellerCatalog($product);

            if ($deleted) {
                // Update status to fully deleted
                $product->update([
                    'in_catalog' => 'deleted',
                    'catalog_deletion_at' => now()
                ]);

                Log::info('Product successfully deleted from Amazon catalog', [
                    'product_id' => $product->id,
                    'connection_pair_id' => $connectionPair->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete product from Amazon catalog', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);

            // Set status to indicate failed deletion
            $product->update([
                'in_catalog' => 'deletion_failed',
                'catalog_deletion_error' => $e->getMessage()
            ]);
        }
    }
}