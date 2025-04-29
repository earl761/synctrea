<?php

namespace App\Services\Api;

use App\Models\Destination;
use App\Services\Api\Exceptions\ApiException;

class WooCommerceApiClient extends AbstractApiClient
{
    protected Destination $destination;
    protected string $consumerKey;
    protected string $consumerSecret;

    public function __construct(Destination $destination)
    {
        $this->destination = $destination;
        $this->baseUrl = rtrim($destination->api_endpoint, '/') . '/wp-json/wc/v3';
        $this->consumerKey = $destination->api_key;
        $this->consumerSecret = $destination->api_secret;
    }

    public function initialize(): void
    {
        if ($this->isInitialized) {
            return;
        }

        $this->setHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);

        $this->setConfig([
            'verify' => true,
            'timeout' => 30,
            'auth' => [$this->consumerKey, $this->consumerSecret],
        ]);

        $this->isInitialized = true;
    }

    public function getProduct(string $sku): array
    {
        $response = $this->request('GET', 'products', [
            'sku' => $sku,
        ]);
        return $this->handleResponse($response)[0] ?? [];
    }

    public function getProducts(array $skus = []): array
    {
        $params = [];
        
        if (!empty($skus)) {
            $params['sku'] = implode(',', $skus);
        }

        $response = $this->request('GET', 'products', $params);
        return $this->handleResponse($response) ?? [];
    }

    public function updateProduct(array $productData): array
    {
        // First check if product exists
        $existingProduct = $this->getProduct($productData['sku']);

        if (empty($existingProduct)) {
            // Create new product
            $response = $this->request('POST', 'products', [
                'product' => $this->formatProductData($productData),
            ]);
        } else {
            // Update existing product
            $response = $this->request('PUT', 'products/' . $existingProduct['id'], [
                'product' => $this->formatProductData($productData, $existingProduct),
            ]);
        }

        return $this->handleResponse($response);
    }

    public function updateInventory(string $sku, int $quantity): array
    {
        $product = $this->getProduct($sku);
        
        if (empty($product)) {
            throw new ApiException('Product not found: ' . $sku);
        }

        $response = $this->request('PUT', 'products/' . $product['id'], [
            'stock_quantity' => $quantity,
        ]);

        return $this->handleResponse($response);
    }

    protected function handleResponse($response): array
    {
        $data = $response->json();

        if ($response->failed()) {
            throw new ApiException(
                'WooCommerce API error: ' . ($data['message'] ?? 'Unknown error'),
                $response->status()
            );
        }

        return $data;
    }

    protected function formatProductData(array $data, array $existingProduct = []): array
    {
        return [
            'name' => $data['name'] ?? null,
            'sku' => $data['sku'],
            'regular_price' => (string) ($data['price'] ?? ''),
            'description' => $data['description'] ?? '',
            'short_description' => $data['short_description'] ?? '',
            'manage_stock' => true,
            'stock_quantity' => $data['quantity'] ?? 0,
            'status' => 'publish',
        ];
    }
}