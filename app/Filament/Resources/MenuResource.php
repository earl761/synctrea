<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Content;
use Datlechin\FilamentMenuBuilder\Resources\MenuResource as BaseMenuResource;

class MenuResource extends BaseMenuResource
{
    protected static ?string $cluster = Content::class;
    
    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'fluentui-navigation-16';

    public static function shouldRegisterNavigation(): bool
    {
        $user = \Filament\Facades\Filament::auth()->user();
        return $user instanceof \App\Models\User && $user->isSuperAdmin();
    }
}
