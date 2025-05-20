<?php

namespace App\Observers;

use App\Models\Company;
use App\Notifications\SubscriptionConfirmation;

class CompanySubscriptionObserver
{
    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        // If company is created with an active subscription
        if ($company->subscription_status === 'active') {
            $company->notify(new SubscriptionConfirmation($company, 'created'));
        }
    }

    /**
     * Handle the Company "updated" event.
     */
    public function updated(Company $company): void
    {
        // Check if subscription status changed
        if ($company->wasChanged('subscription_status')) {
            $newStatus = $company->subscription_status;
            $oldStatus = $company->getOriginal('subscription_status');

            // Handle subscription status changes
            if ($newStatus === 'active' && $oldStatus !== 'active') {
                $company->notify(new SubscriptionConfirmation($company, 'created'));
            } elseif ($newStatus === 'cancelled') {
                $company->notify(new SubscriptionConfirmation($company, 'cancelled'));
            }
        }

        // Check if subscription package changed
        if ($company->wasChanged('subscription_package_id') && $company->subscription_status === 'active') {
            $company->notify(new SubscriptionConfirmation($company, 'updated'));
        }
    }

    /**
     * Handle the Company "deleted" event.
     */
    public function deleted(Company $company): void
    {
        //
    }

    /**
     * Handle the Company "restored" event.
     */
    public function restored(Company $company): void
    {
        //
    }

    /**
     * Handle the Company "force deleted" event.
     */
    public function forceDeleted(Company $company): void
    {
        //
    }
}
