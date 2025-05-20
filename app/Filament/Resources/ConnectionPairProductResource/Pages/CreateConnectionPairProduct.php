<?php

namespace App\Filament\Resources\ConnectionPairProductResource\Pages;

use App\Filament\Resources\ConnectionPairProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateConnectionPairProduct extends CreateRecord
{
    protected static string $resource = ConnectionPairProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!isset($data['connection_pair_id']) && request()->query('connection_pair_id')) {
            $data['connection_pair_id'] = request()->query('connection_pair_id');
        }
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', ['connection_pair_id' => $this->record->connection_pair_id]);
    }
}