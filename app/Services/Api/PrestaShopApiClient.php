<?php

namespace App\Services\Api;

use App\Models\Destination;
use App\Services\Api\Exceptions\ApiException;

class PrestaShopApiClient extends AbstractApiClient
{
    protected Destination $destination;
    protected string $apiKey;

    public function __construct(Destination $destination)
    {
        $this->destination = $destination;
        $this->baseUrl = rtrim($destination->api_endpoint, '/') . '/api';
        $this->apiKey = $destination->api_key;
    }

    public function initialize(): void
    {
        if ($this->isInitialized) {
            return;
        }

        // Set up basic authentication headers
        $this->setHeaders([
            'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Output-Format' => 'JSON',
        ]);

        $this->setConfig([
            'verify' => true,
            'timeout' => 30,
        ]);

        $this->isInitialized = true;
    }

    public function getProduct(string $sku): array
    {
        $response = $this->request('GET', 'products', [
            'filter[reference]' => $sku,
            'display' => 'full',
        ]);
        return $this->handleResponse($response)['products'][0] ?? [];
    }

    public function getProducts(array $skus = []): array
    {
        $params = ['display' => 'full'];
        
        if (!empty($skus)) {
            $params['filter[reference]'] = '[' . implode('|', $skus) . ']';
        }

        $response = $this->request('GET', 'products', $params);
        return $this->handleResponse($response)['products'] ?? [];
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

        $stockId = $product['associations']['stock_availables'][0]['id'];
        
        $response = $this->request('PUT', 'stock_availables/' . $stockId, [
            'stock_available' => [
                'quantity' => $quantity,
            ],
        ]);

        return $this->handleResponse($response);
    }

    public function updatePrice(string $sku, float $price): array
    {
        $product = $this->getProduct($sku);
        
        if (empty($product)) {
            throw new ApiException('Product not found: ' . $sku);
        }

        $response = $this->request('PUT', 'products/' . $product['id'], [
            'product' => [
                'price' => $price,
            ],
        ]);

        return $this->handleResponse($response);
    }

    public function getOrder(string $orderId): array
    {
        $response = $this->request('GET', 'orders/' . $orderId, [
            'display' => 'full',
        ]);
        return $this->handleResponse($response)['order'] ?? [];
    }

    public function getOrders(array $filters = []): array
    {
        $params = array_merge([
            'display' => 'full',
            'sort' => '[id_DESC]',
        ], $filters);

        $response = $this->request('GET', 'orders', $params);
        return $this->handleResponse($response)['orders'] ?? [];
    }

    public function updateOrderStatus(string $orderId, string $status): array
    {
        $statusId = $this->mapOrderStatus($status);
        
        $response = $this->request('PUT', 'orders/' . $orderId, [
            'order' => [
                'current_state' => $statusId,
            ],
        ]);

        return $this->handleResponse($response);
    }

    protected function formatProductData(array $data, array $existing = []): array
    {
        return [
            'reference' => $data['sku'],
            'name' => [['language' => 1, 'value' => $data['name']]],
            'description' => [['language' => 1, 'value' => $data['description'] ?? '']],
            'price' => $data['price'],
            'active' => 1,
            'state' => 1,
            'id_manufacturer' => $this->getManufacturerId($data['manufacturer'] ?? ''),
            'minimal_quantity' => 1,
            'id_category_default' => $this->getCategoryId($data['category'] ?? 'Default'),
            'id_tax_rules_group' => 1,
        ];
    }

    protected function mapOrderStatus(string $status): int
    {
        return match(strtolower($status)) {
            'pending' => 1,
            'payment_accepted' => 2,
            'processing' => 3,
            'shipped' => 4,
            'delivered' => 5,
            'canceled' => 6,
            'refunded' => 7,
            default => throw new ApiException('Unsupported order status: ' . $status),
        };
    }

    protected function getManufacturerId(string $name): int
    {
        // Implementation to get or create manufacturer ID
        return 1; // Default manufacturer ID
    }

    protected function getCategoryId(string $name): int
    {
        // Implementation to get or create category ID
        return 2; // Default category ID
    }

    protected function handleResponse($response): array
    {
        $data = parent::handleResponse($response);

        if (isset($data['errors'])) {
            throw new ApiException(
                implode(', ', array_column($data['errors'], 'message')),
                400
            );
        }

        return $data;
    }
}