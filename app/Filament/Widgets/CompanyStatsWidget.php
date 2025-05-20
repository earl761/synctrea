<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;
use App\Models\SubscriptionPackage;
use App\Models\Supplier;
use App\Models\Destination;

class CompanyStatsWidget extends Widget
{
    protected static string $view = 'filament.widgets.company-stats';

    public function getViewData(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $isAdmin = $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
        $data = [];

        if ($isAdmin) {
            $data['totalCompanies'] = Company::count();
            $data['subscriptionsPerPackage'] = SubscriptionPackage::withCount(['companies' => function ($query) {
                $query->where('subscription_status', 'active');
            }])->get();
            $data['activeSuppliers'] = Supplier::where('is_active', true)->count();
            $data['activeDestinations'] = Destination::where('is_active', true)->count();
        } else if ($user && $user->company_id) {
            $companyId = $user->company_id;
            $data['activeSuppliers'] = Supplier::whereHas('connectionPairs', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->where('is_active', true);
            })->count();
            $data['activeDestinations'] = Destination::whereHas('connectionPairs', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->where('is_active', true);
            })->count();
        }

        $data['isAdmin'] = $isAdmin;
        return $data;
    }

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }
} 