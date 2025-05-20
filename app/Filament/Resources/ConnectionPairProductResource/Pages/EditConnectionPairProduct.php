<?php

namespace App\Filament\Resources\ConnectionPairProductResource\Pages;

use App\Filament\Resources\ConnectionPairProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConnectionPairProduct extends EditRecord
{
    protected static string $resource = ConnectionPairProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', ['connection_pair_id' => $this->record->connection_pair_id]);
    }
}