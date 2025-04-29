<?php

namespace App\Services\Api;

use App\Models\Destination;
use Illuminate\Support\Facades\Http;

class ShopifyApiClient
{
    protected Destination $destination;
    protected string $baseUrl;
    protected string $accessToken;

    public function __construct(Destination $destination)
    {
        $this->destination = $destination;
        $this->baseUrl = rtrim($destination->url, '/') . '/admin/api/2024-01';
        $this->accessToken = $destination->api_key;
    }

    public function findProductBySku(string $sku): ?array
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type' => 'application/json',
        ])->get("{$this->baseUrl}/products.json", [
            'query' => $sku
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to search product in Shopify: ' . $response->body());
        }

        $products = $response->json()['products'] ?? [];
        foreach ($products as $product) {
            foreach ($product['variants'] as $variant) {
                if ($variant['sku'] === $sku) {
                    return $product;
                }
            }
        }

        return null;
    }

    public function syncProduct(array $productData): array
    {
        $existingProduct = $this->findProductBySku($productData['sku']);

        if ($existingProduct) {
            return $this->updateProduct($existingProduct['id'], $productData);
        }

        return $this->createProduct($productData);
    }

    protected function createProduct(array $productData): array
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/products.json", [
            'product' => [
                'title' => $productData['name'],
                'body_html' => $productData['description'],
                'variants' => [
                    [
                        'sku' => $productData['sku'],
                        'price' => $productData['price'],
                        'inventory_quantity' => $productData['quantity'],
                        'inventory_management' => 'shopify'
                    ]
                ]
            ]
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to create product in Shopify: ' . $response->body());
        }

        return $response->json();
    }

    protected function updateProduct(string $productId, array $productData): array
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type' => 'application/json',
        ])->put("{$this->baseUrl}/products/{$productId}.json", [
            'product' => [
                'title' => $productData['name'],
                'body_html' => $productData['description'],
                'variants' => [
                    [
                        'sku' => $productData['sku'],
                        'price' => $productData['price'],
                        'inventory_quantity' => $productData['quantity'],
                        'inventory_management' => 'shopify'
                    ]
                ]
            ]
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to update product in Shopify: ' . $response->body());
        }

        return $response->json();
    }
}