<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ConnectionPair;
use App\Models\Company;
use App\Models\User;

class UpdateConnectionPairsCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first company (or create one if none exists)
        $company = Company::first() ?? Company::create([
            'name' => 'Default Company',
            'slug' => 'default-company',
            'subscription_status' => 'active',
        ]);

        // Update all connection pairs that don't have a company_id
        ConnectionPair::whereNull('company_id')->update([
            'company_id' => $company->id
        ]);
    }
} 