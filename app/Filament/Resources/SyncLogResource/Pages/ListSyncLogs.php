<?php

namespace App\Filament\Resources\SyncLogResource\Pages;

use App\Filament\Resources\SyncLogResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSyncLogs extends ListRecords
{
    protected static string $resource = SyncLogResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('clear_old_logs')
                ->action(function () {
                    $daysToKeep = 30; // Adjust as needed
                    $deleted = $this->getResource()::getModel()
                        ::where('created_at', '<', now()->subDays($daysToKeep))
                        ->delete();

                    $this->notify('success', "Cleared {$deleted} old sync logs.");
                })
                ->requiresConfirmation()
                ->color('danger')
                ->icon('heroicon-o-trash'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            //
        ];
    }
}