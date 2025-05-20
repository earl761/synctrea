<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // Company permissions
            'view_company_details',
            'edit_company_details',
            'manage_company_users',
            'manage_company_subscription',

            // Connection pair permissions
            'create_connection_pairs',
            'edit_connection_pairs',
            'delete_connection_pairs',
            'view_connection_pairs',

            // Pricing rule permissions
            'create_pricing_rules',
            'edit_pricing_rules',
            'delete_pricing_rules',
            'view_pricing_rules',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles
        $superAdmin = Role::create(['name' => config('filament-shield.super_admin.name')]);
        $companyAdmin = Role::create(['name' => 'company_admin']);
        $companyUser = Role::create(['name' => 'company_user']);

        // Assign permissions to roles
        $companyAdmin->givePermissionTo([
            'view_company_details',
            'edit_company_details',
            'manage_company_users',
            'manage_company_subscription',
            'create_connection_pairs',
            'edit_connection_pairs',
            'delete_connection_pairs',
            'view_connection_pairs',
            'create_pricing_rules',
            'edit_pricing_rules',
            'delete_pricing_rules',
            'view_pricing_rules',
        ]);

        $companyUser->givePermissionTo([
            'view_company_details',
            'view_connection_pairs',
            'view_pricing_rules',
        ]);

        // Super admin gets all permissions
        $superAdmin->givePermissionTo(Permission::all());
    }
} 