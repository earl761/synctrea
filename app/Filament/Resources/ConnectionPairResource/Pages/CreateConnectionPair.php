<?php

namespace App\Filament\Resources\ConnectionPairResource\Pages;

use App\Filament\Resources\ConnectionPairResource;
use Filament\Resources\Pages\CreateRecord;

class CreateConnectionPair extends CreateRecord
{
    protected static string $resource = ConnectionPairResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}