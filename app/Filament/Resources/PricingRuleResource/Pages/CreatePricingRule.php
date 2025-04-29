<?php

namespace App\Filament\Resources\PricingRuleResource\Pages;

use App\Filament\Resources\PricingRuleResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePricingRule extends CreateRecord
{
    protected static string $resource = PricingRuleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}