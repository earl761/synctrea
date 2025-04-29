<?php

namespace App\Filament\Resources\ConnectionPairResource\Pages;

use App\Filament\Resources\ConnectionPairResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConnectionPair extends EditRecord
{
    protected static string $resource = ConnectionPairResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}