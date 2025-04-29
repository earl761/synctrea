<?php

namespace Tests\Unit\Services\Api;

use App\Models\Destination;
use App\Services\Api\AmazonSpApiClient;
use App\Services\Api\AmazonSpApiSandbox;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Mockery;

class AmazonSpApiSandboxTest extends TestCase
{
    use WithFaker;

    protected $destination;
    protected $client;
    protected $sandbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->destination = Mockery::mock(Destination::class);
        $this->destination->shouldReceive('getAmazonEndpoint')->andReturn('https://sandbox.sellingpartnerapi-na.amazon.com');
        $this->destination->shouldReceive('getAttribute')->with('region')->andReturn('US');
        $this->destination->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $this->destination->shouldReceive('getAttribute')->with('credentials')->andReturn(['refresh_token' => 'test_refresh_token']);
        $this->destination->shouldReceive('getAttribute')->with('marketplace_id')->andReturn('ATVPDKIKX0DER');
        $this->destination->shouldReceive('getAttribute')->with('seller_id')->andReturn('TEST_SELLER');
        $this->destination->shouldReceive('getAttribute')->with('api_key')->andReturn(encrypt('test_api_key'));
        $this->destination->shouldReceive('getAttribute')->with('api_secret')->andReturn(encrypt('test_api_secret'));

        $this->client = new AmazonSpApiClient($this->destination, true);
        $this->sandbox = new AmazonSpApiSandbox();
    }

    public function test_sandbox_mode_initialization()
    {
        $this->assertTrue($this->client->isSandboxMode());
        $this->assertEquals('https://sandbox.sellingpartnerapi-na.amazon.com', $this->client->getBaseUrl());
    }

    public function test_get_products_in_sandbox()
    {
        $response = $this->client->getProducts(['TEST-SKU-1']);
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('items', $response);
        $this->assertNotEmpty($response['items']);
    }

    public function test_get_inventory_in_sandbox()
    {
        $response = $this->client->getInventory(['TEST-SKU-1']);
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('inventories', $response);
        $this->assertNotEmpty($response['inventories']);
    }

    public function test_get_pricing_in_sandbox()
    {
        $response = $this->client->getPricing(['TEST-SKU-1']);
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('prices', $response);
        $this->assertNotEmpty($response['prices']);
    }

    public function test_update_inventory_in_sandbox()
    {
        $response = $this->client->updateInventory('TEST-SKU-1', 100);
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
    }

    public function test_update_price_in_sandbox()
    {
        $response = $this->client->updatePrice('TEST-SKU-1', 29.99);
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
    }

    public function test_sandbox_mock_response_customization()
    {
        $customResponse = [
            'items' => [
                [
                    'sku' => 'CUSTOM-SKU',
                    'title' => 'Custom Product',
                    'price' => 99.99
                ]
            ]
        ];

        $this->sandbox->setMockResponse('getCatalogItem', $customResponse);
        $response = $this->client->getProducts(['CUSTOM-SKU']);
        
        $this->assertEquals($customResponse, $response);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}