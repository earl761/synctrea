<?php

namespace App\Livewire\Pages;

use Livewire\Component;
use App\Models\SubscriptionPackage;
use Illuminate\Support\Collection;

class PricingPage extends Component
{
    public $title = 'Pricing Plans';
    public $metaDescription = 'Flexible pricing plans for every business size. Get started with SyncTrae today!';
    
    public function render()
    {
        $packages = SubscriptionPackage::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
            
        // If no packages exist in the database, create default ones for display
        if ($packages->isEmpty()) {
            $packages = $this->getDefaultPackages();
        }
            
        return view('livewire.pages.pricing-page', [
            'packages' => $packages
        ])->layout('components.layouts.app', [
            'title' => $this->title,
            'metaDescription' => $this->metaDescription
        ]);
    }

    /**
     * Get default subscription packages for display when none exist in database
     */
    private function getDefaultPackages(): Collection
    {
        return collect([
            (object)[
                'id' => 'basic',
                'name' => 'Basic',
                'description' => 'Perfect for small businesses and startups',
                'price' => 29,
                'features' => [
                    'inventory_tracking' => 'Track up to 1,000 products',
                    'user_accounts' => '3 user accounts',
                    'reporting' => 'Basic reporting',
                    'integrations' => 'Email support',
                    'api_access' => '1 integration'
                ]
            ],
            (object)[
                'id' => 'professional',
                'name' => 'Professional',
                'description' => 'Ideal for growing businesses',
                'price' => 79,
                'features' => [
                    'inventory_tracking' => 'Track up to 10,000 products',
                    'user_accounts' => '10 user accounts',
                    'reporting' => 'Advanced reporting',
                    'integrations' => 'Email + phone support',
                    'api_access' => '5 integrations',
                    'forecasting' => 'Demand forecasting',
                    'barcode' => 'Barcode generation'
                ]
            ],
            (object)[
                'id' => 'enterprise',
                'name' => 'Enterprise',
                'description' => 'For large-scale operations',
                'price' => 199,
                'features' => [
                    'inventory_tracking' => 'Unlimited products',
                    'user_accounts' => 'Unlimited user accounts',
                    'reporting' => 'Custom reporting',
                    'integrations' => 'Priority 24/7 support',
                    'api_access' => 'Unlimited integrations',
                    'forecasting' => 'Advanced forecasting',
                    'barcode' => 'Advanced barcode system',
                    'dedicated' => 'Dedicated account manager',
                    'custom_api' => 'Custom API development'
                ]
            ],
        ]);
    }
}
