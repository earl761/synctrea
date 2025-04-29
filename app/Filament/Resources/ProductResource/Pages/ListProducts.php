<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('sync_all')
                ->action(function () {
                    $products = $this->getResource()::getModel()::where('is_active', true)->get();
                    $successCount = 0;
                    $failCount = 0;

                    foreach ($products as $product) {
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

                            $successCount++;
                        } catch (\Exception $e) {
                            $failCount++;
                            // Log the error
                            \Log::error("Product sync failed for SKU {$product->sku}: " . $e->getMessage());
                        }
                    }

                    $this->notify(
                        'success',
                        "Sync completed: {$successCount} succeeded, {$failCount} failed."
                    );
                })
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            //
        ];
    }
}