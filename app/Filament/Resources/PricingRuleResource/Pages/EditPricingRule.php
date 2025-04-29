<?php

namespace App\Filament\Resources\PricingRuleResource\Pages;

use App\Filament\Resources\PricingRuleResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPricingRule extends EditRecord
{
    protected static string $resource = PricingRuleResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}