<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ConnectionPair;
use App\Models\Product;
use App\Models\PricingRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'company_admin']);
        Role::create(['name' => 'company_user']);
    }

    /** @test */
    public function tenant_scoping_isolates_data_between_companies()
    {
        // Create two companies
        $company1 = Company::factory()->create(['name' => 'Company 1']);
        $company2 = Company::factory()->create(['name' => 'Company 2']);

        // Create users for each company
        $user1 = User::factory()->create(['company_id' => $company1->id]);
        $user2 = User::factory()->create(['company_id' => $company2->id]);
        
        $user1->assignRole('company_admin');
        $user2->assignRole('company_admin');

        // Create data for company 1
        $this->actingAs($user1);
        $connectionPair1 = ConnectionPair::factory()->create();
        $product1 = Product::factory()->create();
        $pricingRule1 = PricingRule::factory()->create();

        // Create data for company 2
        $this->actingAs($user2);
        $connectionPair2 = ConnectionPair::factory()->create();
        $product2 = Product::factory()->create();
        $pricingRule2 = PricingRule::factory()->create();

        // Verify company 1 user can only see their data
        $this->actingAs($user1);
        $this->assertEquals(1, ConnectionPair::count());
        $this->assertEquals(1, Product::count());
        $this->assertEquals(1, PricingRule::count());
        $this->assertEquals($company1->id, ConnectionPair::first()->company_id);
        $this->assertEquals($company1->id, Product::first()->company_id);
        $this->assertEquals($company1->id, PricingRule::first()->company_id);

        // Verify company 2 user can only see their data
        $this->actingAs($user2);
        $this->assertEquals(1, ConnectionPair::count());
        $this->assertEquals(1, Product::count());
        $this->assertEquals(1, PricingRule::count());
        $this->assertEquals($company2->id, ConnectionPair::first()->company_id);
        $this->assertEquals($company2->id, Product::first()->company_id);
        $this->assertEquals($company2->id, PricingRule::first()->company_id);
    }

    /** @test */
    public function super_admin_can_see_all_data()
    {
        // Create two companies
        $company1 = Company::factory()->create(['name' => 'Company 1']);
        $company2 = Company::factory()->create(['name' => 'Company 2']);

        // Create super admin
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        // Create regular users
        $user1 = User::factory()->create(['company_id' => $company1->id]);
        $user2 = User::factory()->create(['company_id' => $company2->id]);
        
        $user1->assignRole('company_admin');
        $user2->assignRole('company_admin');

        // Create data for each company
        $this->actingAs($user1);
        ConnectionPair::factory()->create();
        Product::factory()->create();
        PricingRule::factory()->create();

        $this->actingAs($user2);
        ConnectionPair::factory()->create();
        Product::factory()->create();
        PricingRule::factory()->create();

        // Super admin should see all data
        $this->actingAs($superAdmin);
        $this->assertEquals(2, ConnectionPair::count());
        $this->assertEquals(2, Product::count());
        $this->assertEquals(2, PricingRule::count());
    }

    /** @test */
    public function automatic_company_assignment_on_creation()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('company_admin');

        $this->actingAs($user);

        // Create models without explicitly setting company_id
        $connectionPair = ConnectionPair::factory()->create();
        $product = Product::factory()->create();
        $pricingRule = PricingRule::factory()->create();

        // Verify company_id is automatically set
        $this->assertEquals($company->id, $connectionPair->company_id);
        $this->assertEquals($company->id, $product->company_id);
        $this->assertEquals($company->id, $pricingRule->company_id);
    }

    /** @test */
    public function tenant_middleware_blocks_users_without_company()
    {
        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('company_user');

        $response = $this->actingAs($user)->get('/admin');
        
        $response->assertStatus(403);
    }

    /** @test */
    public function tenant_middleware_blocks_inactive_subscriptions()
    {
        $company = Company::factory()->create([
            'subscription_status' => 'inactive'
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('company_admin');

        $response = $this->actingAs($user)->get('/admin');
        
        $response->assertStatus(403);
    }

    /** @test */
    public function super_admin_bypasses_tenant_restrictions()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $response = $this->actingAs($superAdmin)->get('/admin');
        
        $response->assertStatus(200);
    }
}