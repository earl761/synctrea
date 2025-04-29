<?php

namespace App\Filament\Resources\ConnectionPairProductResource\Pages;

use App\Filament\Resources\ConnectionPairProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateConnectionPairProduct extends CreateRecord
{
    protected static string $resource = ConnectionPairProductResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}