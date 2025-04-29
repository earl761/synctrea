<?php

namespace App\Filament\Resources\DestinationResource\Pages;

use App\Filament\Resources\DestinationResource;
use App\Services\Api\AmazonSpApiClient;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDestination extends EditRecord
{
    protected static string $resource = DestinationResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('test_connection')
                ->action(function () {
                    $destination = $this->record;
                    try {
                        $client = new AmazonSpApiClient($destination);
                        $client->initialize();
                        $this->notify('success', 'Amazon SP-API connection successful!');
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

    // protected function mutateFormDataBeforeSave(array $data): array
    // {
    //     $data['credentials'] = [
    //         'refresh_token' => $data['credentials']['refresh_token'] ?? $this->record->credentials['refresh_token'] ?? null,
    //     ];

    //     return $data;
    // }
}