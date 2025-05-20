<?php

namespace App\Filament\Resources;

use Datlechin\FilamentMenuBuilder\Resources\MenuResource as BaseMenuResource;

class MenuResource extends BaseMenuResource
{
    protected static ?int $navigationSort = 0;

    protected static ?string $navigationIcon = 'fluentui-navigation-16';

    public static function getNavigationGroup(): ?string
    {
        return __("menu.nav_group.sites");
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = \Filament\Facades\Filament::auth()->user();
        return $user instanceof \App\Models\User && $user->isSuperAdmin();
    }
}
