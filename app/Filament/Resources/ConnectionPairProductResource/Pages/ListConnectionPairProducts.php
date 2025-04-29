<?php

namespace App\Filament\Resources\ConnectionPairProductResource\Pages;

use App\Filament\Resources\ConnectionPairProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConnectionPairProducts extends ListRecords
{
    protected static string $resource = ConnectionPairProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}