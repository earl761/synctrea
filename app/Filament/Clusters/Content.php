<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Content extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('Content Management');
    }

    public static function getBreadcrumb(): string
    {
        return __('Content');
    }
}