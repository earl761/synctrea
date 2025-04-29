<?php

namespace Tests\Unit\Services\Api;

use App\Models\Destination;
use App\Services\Api\AmazonSpApiClient;
use App\Services\Api\Exceptions\ApiException;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Mockery;

class AmazonSpApiClientTest extends TestCase
{
    use WithFaker;

    protected $destination;
    protected $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->destination = Mockery::mock(Destination::class);
        $this->destination->shouldReceive('getAmazonEndpoint')->andReturn('https://sellingpartnerapi.amazon.com');
        $this->destination->shouldReceive('getAttribute')->with('region')->andReturn('US');
        $this->destination->shouldReceive('getAttribute')->with('credentials')->andReturn(['refresh_token' => 'test_refresh_token']);
        $this->destination->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $this->destination->shouldReceive('getAttribute')->with('api_key')->andReturn(encrypt('test_api_key'));
        $this->destination->shouldReceive('getAttribute')->with('api_secret')->andReturn(encrypt('test_api_secret'));

        $this->client = Mockery::mock(AmazonSpApiClient::class, [$this->destination])->makePartial();
    }

    public function test_get_access_token_caches_token()
    {
        Cache::shouldReceive('get')
            ->once()
            ->with('amazon_sp_api_token_' . $this->destination->id)
            ->andReturn(null);

        Cache::shouldReceive('put')
            ->once()
            ->with(
                'amazon_sp_api_token_' . $this->destination->id,
                'test_access_token',
                3500
            );

        $this->client->initialize();
    }

    public function test_get_products_with_valid_skus()
    {
        $skus = ['SKU123', 'SKU456'];
        $expectedResponse = [
            'items' => [
                [
                    'sku' => 'SKU123',
                    'title' => 'Test Product 1'
                ],
                [
                    'sku' => 'SKU456',
                    'title' => 'Test Product 2'
                ]
            ]
        ];

        $this->client->method('request')
            ->willReturn($expectedResponse);

        $result = $this->client->getProducts($skus);

        $this->assertEquals($expectedResponse['items'], $result);
    }

    public function test_get_products_handles_api_error()
    {
        $this->expectException(ApiException::class);

        $errorResponse = [
            'errors' => [
                [
                    'code' => 'InvalidInput',
                    'message' => 'Invalid SKU format'
                ]
            ]
        ];

        $this->client->method('request')
            ->willReturn($errorResponse);

        $this->client->getProducts(['invalid-sku']);
    }

    public function test_update_inventory_success()
    {
        $sku = 'TEST-SKU-123';
        $quantity = 10;
        $expectedResponse = [
            'success' => true
        ];

        $this->client->method('request')
            ->willReturn($expectedResponse);

        $result = $this->client->updateInventory($sku, $quantity);

        $this->assertTrue($result['success']);
    }

    public function test_update_price_success()
    {
        $sku = 'TEST-SKU-123';
        $price = 29.99;
        $expectedResponse = [
            'success' => true
        ];

        $this->client->method('request')
            ->willReturn($expectedResponse);

        $result = $this->client->updatePrice($sku, $price);

        $this->assertTrue($result['success']);
    }
}