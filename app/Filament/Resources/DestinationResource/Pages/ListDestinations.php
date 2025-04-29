<?php

namespace App\Filament\Resources\DestinationResource\Pages;

use App\Filament\Resources\DestinationResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDestinations extends ListRecords
{
    protected static string $resource = DestinationResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            //
        ];
    }
}