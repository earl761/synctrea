<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('sync_now')
                ->action(function () {
                    $product = $this->record;

                    try {
                        // Initialize API clients
                        $sourceClient = new $product->supplier->api_client_class($product->supplier);
                        $destClient = new $product->destination->api_client_class($product->destination);

                        $sourceClient->initialize();
                        $destClient->initialize();

                        // Sync product data
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

                        $this->notify('success', 'Product synced successfully!');
                    } catch (\Exception $e) {
                        $this->notify('danger', 'Sync failed: ' . $e->getMessage());
                        \Log::error("Product sync failed for SKU {$product->sku}: " . $e->getMessage());
                    }
                })
                ->icon('heroicon-o-pencil-square')
                ->color('success'),
            Actions\Action::make('update_price')
                ->action(function () {
                    $product = $this->record;

                    try {
                        $destClient = new $product->destination->api_client_class($product->destination);
                        $destClient->initialize();
                        $destClient->updatePrice($product->sku, $product->selling_price);

                        $this->notify('success', 'Price updated successfully!');
                    } catch (\Exception $e) {
                        $this->notify('danger', 'Price update failed: ' . $e->getMessage());
                        \Log::error("Price update failed for SKU {$product->sku}: " . $e->getMessage());
                    }
                })
                ->icon('heroicon-o-currency-dollar')
                ->color('warning'),
            Actions\Action::make('update_inventory')
                ->action(function () {
                    $product = $this->record;

                    try {
                        $destClient = new $product->destination->api_client_class($product->destination);
                        $destClient->initialize();
                        $destClient->updateInventory($product->sku, $product->stock_quantity);

                        $this->notify('success', 'Inventory updated successfully!');
                    } catch (\Exception $e) {
                        $this->notify('danger', 'Inventory update failed: ' . $e->getMessage());
                        \Log::error("Inventory update failed for SKU {$product->sku}: " . $e->getMessage());
                    }
                })
                ->icon('heroicon-o-archive')
                ->color('primary'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}