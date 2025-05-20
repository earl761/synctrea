<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\ConnectionPairsWidget;
use App\Filament\Widgets\CompanyStatsWidget;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static string $view = 'filament.pages.dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            CompanyStatsWidget::class,
            ConnectionPairsWidget::class,
        ];
    }
} 