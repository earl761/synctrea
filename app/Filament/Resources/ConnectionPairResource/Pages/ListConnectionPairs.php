<?php

namespace App\Filament\Resources\ConnectionPairResource\Pages;

use App\Filament\Resources\ConnectionPairResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConnectionPairs extends ListRecords
{
    protected static string $resource = ConnectionPairResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}