<?php

namespace App\Filament\Resources\SyncLogResource\Pages;

use App\Filament\Resources\SyncLogResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSyncLog extends EditRecord
{
    protected static string $resource = SyncLogResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('retry_sync')
                ->action(function () {
                    $syncLog = $this->record;
                    $product = $syncLog->product;

                    try {
                        switch ($syncLog->sync_type) {
                            case 'inventory':
                                $destClient = new $product->destination->api_client_class($product->destination);
                                $destClient->initialize();
                                $destClient->updateInventory($product->sku, $product->stock_quantity);
                                break;
                            case 'price':
                                $destClient = new $product->destination->api_client_class($product->destination);
                                $destClient->initialize();
                                $destClient->updatePrice($product->sku, $product->selling_price);
                                break;
                            case 'product':
                                $sourceClient = new $product->supplier->api_client_class($product->supplier);
                                $destClient = new $product->destination->api_client_class($product->destination);
                                
                                $sourceClient->initialize();
                                $destClient->initialize();
                                
                                $sourceData = $sourceClient->getProduct($product->sku);
                                $destClient->updateProduct([
                                    'sku' => $product->sku,
                                    'name' => $sourceData['name'] ?? $product->name,
                                    'price' => $product->selling_price,
                                    'quantity' => $sourceData['quantity'] ?? $product->stock_quantity,
                                ]);
                                break;
                        }

                        $syncLog->update([
                            'status' => 'success',
                            'message' => 'Retry successful',
                        ]);

                        $this->notify('success', 'Sync retry successful!');
                    } catch (\Exception $e) {
                        $syncLog->update([
                            'status' => 'failed',
                            'message' => 'Retry failed: ' . $e->getMessage(),
                        ]);
                        $this->notify('danger', 'Sync retry failed: ' . $e->getMessage());
                    }
                })
                ->visible(fn () => $this->record->status === 'failed')
                ->icon('heroicon-o-arrow-path')
                ->color('warning'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}