<?php

namespace App\Services\Api;

use App\Models\Destination;
use App\Services\Api\Exceptions\ApiException;

class NeweggApiClient extends AbstractApiClient
{
    protected Destination $destination;
    protected string $apiKey;
    protected string $secretKey;
    protected string $sellerId;
    protected string $marketplace;

    public function __construct(Destination $destination)
    {
        $this->destination = $destination;
        $this->marketplace = str_contains($destination->api_endpoint, '.ca') ? 'NEWEGG_CA' : 'NEWEGG_US';
        $this->baseUrl = rtrim($destination->api_endpoint, '/') . '/marketplace/api/v1';
        $this->apiKey = $destination->api_key;
        $this->secretKey = $destination->api_secret;
        $this->sellerId = $destination->seller_id;
    }

    public function initialize(): void
    {
        if ($this->isInitialized) {
            return;
        }

        $timestamp = gmdate('Y-m-d\\TH:i:s\\Z');
        $signature = hash_hmac('sha256', $this->sellerId . $timestamp, $this->secretKey);

        $this->setHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => $this->apiKey,
            'X-NEWEGG-MARKETPLACE' => $this->marketplace,
            'X-NEWEGG-SELLERID' => $this->sellerId,
            'X-NEWEGG-TIMESTAMP' => $timestamp,
            'X-NEWEGG-SIGNATURE' => $signature,
        ]);

        $this->setConfig([
            'verify' => true,
            'timeout' => 30,
        ]);

        $this->isInitialized = true;
    }

    public function getProduct(string $sku): array
    {
        $response = $this->request('GET', 'seller/inventory/item/' . $sku);
        return $this->handleResponse($response)['Item'] ?? [];
    }

    public function getProducts(array $skus = []): array
    {
        if (empty($skus)) {
            $response = $this->request('GET', 'seller/inventory/item/list');
        } else {
            $response = $this->request('POST', 'seller/inventory/item/batch', [
                'RequestBody' => [
                    'SellerPartNumber' => $skus
                ]
            ]);
        }
        
        return $this->handleResponse($response)['ItemList'] ?? [];
    }

    public function updateProduct(array $productData): array
    {
        $formattedData = $this->formatProductData($productData);
        
        $response = $this->request('PUT', 'seller/inventory/item/management', [
            'RequestBody' => [
                'Item' => $formattedData
            ]
        ]);

        return $this->handleResponse($response);
    }

    public function updateInventory(string $sku, int $quantity): array
    {
        $response = $this->request('PUT', 'seller/inventory/item/inventory', [
            'RequestBody' => [
                'Item' => [
                    'SellerPartNumber' => $sku,
                    'Inventory' => $quantity,
                ]
            ]
        ]);

        return $this->handleResponse($response);
    }

    protected function handleResponse($response): array
    {
        $data = $response->json();

        if ($response->failed() || ($data['IsSuccess'] ?? true) === false) {
            throw new ApiException(
                'Newegg API error: ' . ($data['ResponseBody']['Errors'][0]['Message'] ?? 'Unknown error'),
                $response->status()
            );
        }

        return $data['ResponseBody'] ?? [];
    }

    protected function formatProductData(array $data, array $existingProduct = []): array
    {
        $isExistingItem = !empty($data['manufacturer_name']) || !empty($existingProduct);
        
        $baseData = [
            'SellerPartNumber' => $data['sku'],
            'ItemName' => $data['name'] ?? '',
            'Description' => $data['description'] ?? '',
            'UnitPrice' => (float) ($data['price'] ?? 0),
            'Inventory' => $data['quantity'] ?? 0,
            'Status' => 1, // 1 for Active
            'PacksOrSets' => 1,
            'ItemCondition' => 1, // 1 for New
            'ShippingRestriction' => 0, // 0 for No restriction
            'PackageLength' => (float) ($data['package_length'] ?? 1),
            'PackageHeight' => (float) ($data['package_height'] ?? 1),
            'PackageWidth' => (float) ($data['package_width'] ?? 1),
            'PackageWeight' => (float) ($data['package_weight'] ?? 1),
            'Condition' => $data['condition'] ?? 1, // 1 for New
            'SellerWarranty' => $data['warranty'] ?? '',
            'ManufacturerPartNumber' => $data['mpn'] ?? '',
            'UPC' => $data['upc'] ?? '',
            'ISBN' => $data['isbn'] ?? '',
            'Currency' => $this->marketplace === 'NEWEGG_CA' ? 'CAD' : 'USD',
            'ShipByNewegg' => 0, // 0 for Self-fulfilled
            'WebsiteShortTitle' => substr($data['name'] ?? '', 0, 200),
            'BulletDescription' => $data['bullet_points'] ?? [],
            'ItemImages' => array_map(function($url) {
                return ['ImageUrl' => $url];
            }, $data['images'] ?? [])
        ];

        if ($isExistingItem) {
            // Additional fields required for existing item creation feed
            $existingItemData = [
                'ManufacturerName' => $data['manufacturer_name'] ?? '',
                'ShippingR2R' => $data['shipping_r2r'] ?? 0, // 0 for No, 1 for Yes
                'ItemCategory' => $data['category'] ?? '',
                'SubCategory1' => $data['subcategory_1'] ?? '',
                'SubCategory2' => $data['subcategory_2'] ?? '',
                'SubCategory3' => $data['subcategory_3'] ?? '',
                'ItemType' => $data['item_type'] ?? '',
                'ItemGroupCode' => $data['group_code'] ?? '',
                'ItemConditionDescription' => $data['condition_description'] ?? '',
                'ItemDimension' => [
                    'ItemLength' => (float) ($data['item_length'] ?? $data['package_length'] ?? 1),
                    'ItemWidth' => (float) ($data['item_width'] ?? $data['package_width'] ?? 1),
                    'ItemHeight' => (float) ($data['item_height'] ?? $data['package_height'] ?? 1),
                    'ItemWeight' => (float) ($data['item_weight'] ?? $data['package_weight'] ?? 1),
                ],
                'ItemPackage' => [
                    'PackageLength' => (float) ($data['package_length'] ?? 1),
                    'PackageWidth' => (float) ($data['package_width'] ?? 1),
                    'PackageHeight' => (float) ($data['package_height'] ?? 1),
                    'PackageWeight' => (float) ($data['package_weight'] ?? 1),
                ],
                'CountryOfOrigin' => $data['country_of_origin'] ?? 'USA',
                'PropCode65Warning' => $data['prop_65_warning'] ?? false,
                'PropCode65WarningText' => $data['prop_65_warning_text'] ?? ''
            ];

            return array_merge($baseData, $existingItemData);
        }

        return $baseData;
    }
}