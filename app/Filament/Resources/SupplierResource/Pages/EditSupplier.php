<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplier extends EditRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('test_connection')
                ->action(function () {
                    $supplier = $this->record;
                    try {
                        $client = new $supplier->api_client_class($supplier);
                        $client->initialize();
                        $this->notify('success', 'Connection successful!');
                    } catch (\Exception $e) {
                        $this->notify('danger', 'Connection failed: ' . $e->getMessage());
                    }
                })
                ->color('success')
                ->icon('heroicon-o-check-circle'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}