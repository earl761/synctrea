<?php

namespace App\Filament\Resources\ConnectionPairResource\Pages;

use App\Filament\Resources\ConnectionPairResource;
use App\Models\PricingRule;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateConnectionPair extends CreateRecord
{
    protected static string $resource = ConnectionPairResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getState();
        
        // Create pricing rule if requested
        if ($data['create_pricing_rule'] ?? false) {
            $this->createPricingRule($data);
        }
    }

    private function createPricingRule(array $data): void
    {
        try {
            PricingRule::create([
                'company_id' => $this->record->company_id,
                'name' => $data['pricing_rule_name'],
                'type' => PricingRule::TYPE_GLOBAL_CONNECTION,
                'supplier_id' => $this->record->supplier_id,
                'destination_id' => $this->record->destination_id,
                'rule_type' => $data['pricing_rule_type'],
                'value' => $data['pricing_rule_value'] ?? null,
                'percentage_value' => $data['pricing_rule_percentage'] ?? null,
                'amount_value' => $data['pricing_rule_amount'] ?? null,
                'priority' => $data['pricing_rule_priority'] ?? 0,
                'is_active' => true,
            ]);

            Notification::make()
                ->success()
                ->title('Pricing rule created')
                ->body('The pricing rule has been created and will be applied to products in this connection pair.')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Failed to create pricing rule')
                ->body('There was an error creating the pricing rule: ' . $e->getMessage())
                ->send();
        }
    }
}