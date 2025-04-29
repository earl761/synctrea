<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Services\Api\AmazonSpApiClient;
use Tests\TestCase;

class AmazonSpApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_amazon_sp_api_authentication()
    {
        $destinations = [
            'US' => Destination::where('region', 'US')->first(),
            'CA' => Destination::where('region', 'CA')->first(),
            'MX' => Destination::where('region', 'MX')->first(),
        ];

        foreach ($destinations as $region => $destination) {
            if (!$destination) {
                $this->markTestSkipped("No destination found for region: {$region}");
                continue;
            }

            $client = new AmazonSpApiClient($destination);
            
            try {
                // Test authentication by making a simple catalog API call
                $response = $client->getProducts(['TEST-SKU-001']);
                
                // We don't care about the actual product data,
                // just that the API call succeeded without throwing an exception
                $this->assertTrue(true, "Successfully authenticated with {$region} marketplace");
            } catch (\Exception $e) {
                $this->fail("Failed to authenticate with {$region} marketplace: " . $e->getMessage());
            }
        }
    }
}