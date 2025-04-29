<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $product = $this->record;

        try {
            // Initialize API clients
            $sourceClient = new $product->supplier->api_client_class($product->supplier);
            $destClient = new $product->destination->api_client_class($product->destination);

            $sourceClient->initialize();
            $destClient->initialize();

            // Sync initial product data
            $sourceData = $sourceClient->getProduct($product->sku);
            $destClient->updateProduct([
                'sku' => $product->sku,
                'name' => $sourceData['name'] ?? $product->name,
                'price' => $product->selling_price,
                'quantity' => $sourceData['quantity'] ?? $product->stock_quantity,
            ]);

            $product->update([
                'last_synced_at' => now(),
                'stock_quantity' => $sourceData['quantity'] ?? $product->stock_quantity,
            ]);

            $this->notify('success', 'Product created and synced successfully!');
        } catch (\Exception $e) {
            $this->notify('warning', 'Product created but sync failed: ' . $e->getMessage());
            \Log::error("Initial product sync failed for SKU {$product->sku}: " . $e->getMessage());
        }
    }
}