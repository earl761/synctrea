<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Services\Api\AmazonSpApiClient;
use Tests\TestCase;

class AmazonSpApiClientTest extends TestCase
{
    protected AmazonSpApiClient $client;
    protected Destination $destination;

    protected function setUp(): void
    {
        parent::setUp();

        $this->destination = Destination::factory()->create([
            'name' => 'Amazon US Test',
            'type' => 'amazon',
            'region' => 'US',
            'api_key' => encrypt('test_key'),
            'api_secret' => encrypt('test_secret'),
            'credentials' => [
                'refresh_token' => 'test_refresh_token',
                'client_id' => 'test_client_id',
                'client_secret' => 'test_client_secret'
            ],
            'is_active' => true
        ]);

        $this->client = new AmazonSpApiClient($this->destination, true);
    }

    public function test_get_products()
    {
        $response = $this->client->getProducts(['TEST-SKU-001']);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('payload', $response);
    }

    public function test_get_inventory()
    {
        $response = $this->client->getInventory(['TEST-SKU-001']);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('inventorySummaries', $response);
    }

    public function test_get_pricing()
    {
        $response = $this->client->getPricing(['TEST-SKU-001']);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('price', $response);
    }

    public function test_update_product()
    {
        $productData = [
            'sku' => 'TEST-SKU-001',
            'title' => 'Test Product',
            'description' => 'Test product description',
        ];

        $response = $this->client->updateProduct($productData);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
    }

    public function test_update_inventory()
    {
        $response = $this->client->updateInventory('TEST-SKU-001', 100);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
    }

    public function test_update_price()
    {
        $response = $this->client->updatePrice('TEST-SKU-001', 29.99);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
    }
}