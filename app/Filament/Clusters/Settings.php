<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Settings extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?int $navigationSort = 99;
    
    public static function getNavigationLabel(): string
    {
        return 'Administration';
    }
    
    public static function getClusterBreadcrumb(): string
    {
        return 'Administration';
    }
}