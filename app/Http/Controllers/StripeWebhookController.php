<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Notifications\SubscriptionConfirmation;
use Illuminate\Http\Request;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;

class StripeWebhookController extends CashierController
{
    /**
     * Handle subscription created.
     *
     * @param  array  $payload
     * @return void
     */
    public function handleCustomerSubscriptionCreated($payload)
    {
        $subscription = $payload['data']['object'];
        $companyId = $subscription['metadata']['company_id'] ?? null;
        $packageId = $subscription['metadata']['package_id'] ?? null;

        if ($companyId && $packageId) {
            $company = Company::find($companyId);
            
            if ($company) {
                $company->update([
                    'stripe_subscription_id' => $subscription['id'],
                    'stripe_subscription_status' => $subscription['status'],
                    'subscription_status' => 'active',
                    'subscription_package_id' => $packageId,
                    'subscription_ends_at' => now()->addDays($subscription['current_period_end']),
                ]);

                $company->notify(new SubscriptionConfirmation($company, 'created'));
            }
        }
    }

    /**
     * Handle subscription updated.
     *
     * @param  array  $payload
     * @return void
     */
    public function handleCustomerSubscriptionUpdated($payload)
    {
        $subscription = $payload['data']['object'];
        $companyId = $subscription['metadata']['company_id'] ?? null;

        if ($companyId) {
            $company = Company::find($companyId);
            
            if ($company) {
                $company->syncSubscriptionStatus();
            }
        }
    }

    /**
     * Handle subscription deleted.
     *
     * @param  array  $payload
     * @return void
     */
    public function handleCustomerSubscriptionDeleted($payload)
    {
        $subscription = $payload['data']['object'];
        $companyId = $subscription['metadata']['company_id'] ?? null;

        if ($companyId) {
            $company = Company::find($companyId);
            
            if ($company) {
                $company->update([
                    'subscription_status' => 'cancelled',
                ]);

                $company->notify(new SubscriptionConfirmation($company, 'cancelled'));
            }
        }
    }

    /**
     * Handle payment failed.
     *
     * @param  array  $payload
     * @return void
     */
    public function handleInvoicePaymentFailed($payload)
    {
        $subscription = $payload['data']['object'];
        $companyId = $subscription['metadata']['company_id'] ?? null;

        if ($companyId) {
            $company = Company::find($companyId);
            
            if ($company) {
                $company->update([
                    'subscription_status' => 'inactive',
                ]);

                // You might want to send a payment failed notification here
            }
        }
    }
} 